<?php namespace Pz\Doctrine\Rest\Tests\Action;

use Pz\Doctrine\Rest\Action\CollectionAction;
use Pz\Doctrine\Rest\Action\CreateAction;
use Pz\Doctrine\Rest\Action\ItemAction;
use Pz\Doctrine\Rest\Exceptions\RestException;
use Pz\Doctrine\Rest\RestRepository;
use Pz\Doctrine\Rest\RestRequest;
use Pz\Doctrine\Rest\RestResponse;
use Pz\Doctrine\Rest\RestResponseFactory;
use Pz\Doctrine\Rest\Tests\Entities\Transformers\UserTransformer;
use Pz\Doctrine\Rest\Tests\Entities\User;
use Pz\Doctrine\Rest\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ActionsWithErrorsTest extends TestCase
{

    public function test_validation()
    {
        $action = new CreateAction(
            new RestRepository($this->em, $this->em->getClassMetadata(User::class)),
            new UserTransformer()
        );

        $request = new RestRequest(new Request([], ['data' => ['attributes' => []]]));
        $response = $action->dispatch($request);
        $this->assertInstanceOf(RestResponse::class, $response);
        $this->assertEquals(
            [
                'errors' => [
                    [
                        'code' => 'validation',
                        'source' => ['pointer' => 'root', 'field' => 'email'],
                        'detail' => 'This value should not be null.'
                    ],
                    [
                        'code' => 'validation',
                        'source' => ['pointer' => 'root', 'field' => 'name'],
                        'detail' => 'This value should not be null.'
                    ],
                ]
            ],
            json_decode($response->getContent(), true)
        );

        $request = new RestRequest(new Request([], ['data' => ['attributes' => [
            'name' => 'Test',
            'email' => 'wrong-email',
        ]]]));
        $response = $action->dispatch($request);
        $this->assertInstanceOf(RestResponse::class, $response);
        $this->assertEquals(
            [
                'errors' => [
                    [
                        'code' => 'validation',
                        'source' => ['pointer' => 'root', 'field' => 'email'],
                        'detail' => 'This value is not a valid email address.'
                    ],
                ]
            ],
            json_decode($response->getContent(), true)
        );

    }

    public function test_exception()
    {
        $action = new ItemAction(
            new RestRepository($this->em, $this->em->getClassMetadata(User::class)),
            function () {
                throw new RestException();
            }
        );

        $request = new RestRequest(new Request(['id' => 1]));
        $response = $action->dispatch($request);
        $this->assertInstanceOf(RestResponse::class, $response);
        $this->assertEquals(RestResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function test_not_found()
    {
        $action = new ItemAction(
            new RestRepository($this->em, $this->em->getClassMetadata(User::class)),
            new UserTransformer()
        );

        $response = $action->dispatch($request = new RestRequest(new Request(['id' => 666])));

        $this->assertInstanceOf(RestResponse::class, $response);
        $this->assertEquals(RestResponse::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
