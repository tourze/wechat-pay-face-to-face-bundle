<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;
use WechatPayFaceToFaceBundle\Controller\CloseOrderController;
use WechatPayFaceToFaceBundle\Controller\CreateOrderController;
use WechatPayFaceToFaceBundle\Controller\FaceToFaceApiInfoController;
use WechatPayFaceToFaceBundle\Controller\GetOrderController;
use WechatPayFaceToFaceBundle\Controller\ListOrdersController;
use WechatPayFaceToFaceBundle\Controller\PollOrderStatusController;
use WechatPayFaceToFaceBundle\Controller\QueryOrderController;

#[AutoconfigureTag(name: 'routing.loader')]
class AttributeControllerLoader extends Loader implements RoutingAutoLoaderInterface
{
    private AttributeRouteControllerLoader $controllerLoader;

    private RouteCollection $collection;

    public function __construct()
    {
        parent::__construct();
        $this->controllerLoader = new AttributeRouteControllerLoader();

        $this->collection = new RouteCollection();
        $this->collection->addCollection($this->controllerLoader->load(FaceToFaceApiInfoController::class));
        $this->collection->addCollection($this->controllerLoader->load(CreateOrderController::class));
        $this->collection->addCollection($this->controllerLoader->load(QueryOrderController::class));
        $this->collection->addCollection($this->controllerLoader->load(CloseOrderController::class));
        $this->collection->addCollection($this->controllerLoader->load(PollOrderStatusController::class));
        $this->collection->addCollection($this->controllerLoader->load(ListOrdersController::class));
        $this->collection->addCollection($this->controllerLoader->load(GetOrderController::class));
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return false;
    }

    public function autoload(): RouteCollection
    {
        return $this->collection;
    }
}