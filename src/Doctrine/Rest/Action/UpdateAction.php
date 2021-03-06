<?php namespace Pz\Doctrine\Rest\Action;

use Pz\Doctrine\Rest\Resource\Item;
use Pz\Doctrine\Rest\Traits\CanHydrate;
use Pz\Doctrine\Rest\RestAction;
use Pz\Doctrine\Rest\Contracts\RestRequestContract;
use Pz\Doctrine\Rest\RestResponse;
use Pz\Doctrine\Rest\Traits\CanValidate;

class UpdateAction extends RestAction
{
    use CanHydrate;
    use CanValidate;

    /**
     * @param RestRequestContract $request
     *
     * @return RestResponse
     */
    public function handle($request)
    {
        $entity = $this->repository()->findById($request->getId());

        $this->authorize($request, $entity);
        $this->hydrateEntity($entity, $request->getData());
        $this->validateEntity($entity);
        $this->repository()->getEntityManager()->flush();

        return $this->response()->resource($request,
            new Item($entity, $this->transformer())
        );
    }
}
