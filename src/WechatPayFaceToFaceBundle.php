<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\EasyAdminMenuBundle\EasyAdminMenuBundle;
use Tourze\JsonRPCLockBundle\JsonRPCLockBundle;
use Tourze\JsonRPCSecurityBundle\JsonRPCSecurityBundle;
use Tourze\Symfony\CronJob\CronJobBundle;

class WechatPayFaceToFaceBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            EasyAdminMenuBundle::class => ['all' => true],
            SecurityBundle::class => ['all' => true],
            JsonRPCLockBundle::class => ['all' => true],
            JsonRPCSecurityBundle::class => ['all' => true],
            CronJobBundle::class => ['all' => true],
        ];
    }
}
