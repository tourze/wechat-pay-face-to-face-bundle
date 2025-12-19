<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

final class WechatPayFaceToFaceExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }

    public function prepend(ContainerBuilder $container): void
    {
        // 配置框架路由
        if ($container->hasExtension('framework')) {
            $container->prependExtensionConfig('framework', [
                'router' => [
                    'resource' => '@WechatPayFaceToFaceBundle/config/routes/routes.yaml',
                ],
            ]);
        }

        // 配置 EasyAdmin
        if ($container->hasExtension('easy_admin')) {
            $container->prependExtensionConfig('easy_admin', [
                'entities' => [
                    'FaceToFaceOrder' => [
                        'class' => 'WechatPayFaceToFaceBundle\\Entity\\FaceToFaceOrder',
                        'controller' => 'WechatPayFaceToFaceBundle\\Controller\\Admin\\FaceToFaceOrderCrudController',
                    ],
                ],
            ]);
        }
    }
}
