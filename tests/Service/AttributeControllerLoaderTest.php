<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Routing\RouteCollection;
use WechatPayFaceToFaceBundle\Service\AttributeControllerLoader;

#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 设置测试环境
    }

    public function testLoadReturnsRouteCollection(): void
    {
        // 从容器中获取服务实例
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->load('some_resource');

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testLoadWithNullType(): void
    {
        // 从容器中获取服务实例
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->load('some_resource', null);

        $this->assertInstanceOf(RouteCollection::class, $collection);
    }

    public function testSupportsAlwaysReturnsFalse(): void
    {
        // 从容器中获取服务实例
        $loader = self::getService(AttributeControllerLoader::class);
        $this->assertFalse($loader->supports('some_resource'));
        $this->assertFalse($loader->supports('some_resource', 'some_type'));
    }

    public function testAutoloadReturnsRouteCollection(): void
    {
        // 从容器中获取服务实例
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testLoadAndAutoloadReturnSameCollection(): void
    {
        // 从容器中获取服务实例
        $loader = self::getService(AttributeControllerLoader::class);
        $loadCollection = $loader->load('some_resource');
        $autoloadCollection = $loader->autoload();

        $this->assertSame($loadCollection, $autoloadCollection);
    }

    public function testImplementsLoaderInterface(): void
    {
        // 从容器中获取服务实例
        $loader = self::getService(AttributeControllerLoader::class);
        $this->assertInstanceOf(LoaderInterface::class, $loader);
    }
}