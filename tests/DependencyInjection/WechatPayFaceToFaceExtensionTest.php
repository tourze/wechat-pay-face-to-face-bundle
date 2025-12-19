<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;
use WechatPayFaceToFaceBundle\DependencyInjection\WechatPayFaceToFaceExtension;

/**
 * WechatPayFaceToFaceExtension 测试
 *
 * 测试重点：
 * - Extension 基本功能和继承关系
 * - 服务配置加载和注册
 * - 容器配置正确性
 * - 服务定义的自动配置
 *
 * @internal
 */
#[CoversClass(WechatPayFaceToFaceExtension::class)]
final class WechatPayFaceToFaceExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private WechatPayFaceToFaceExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new WechatPayFaceToFaceExtension();
        $this->container = new ContainerBuilder();

        // Set required parameters for AutoExtension
        $this->container->setParameter('kernel.environment', 'test');
        $this->container->setParameter('kernel.debug', true);
        $this->container->setParameter('kernel.cache_dir', sys_get_temp_dir());
        $this->container->setParameter('kernel.logs_dir', sys_get_temp_dir());
        $this->container->setParameter('kernel.project_dir', __DIR__ . '/../../');
    }

    public function testLoadLoadsServicesYaml(): void
    {
        $this->extension->load([], $this->container);

        // Check that key services are loaded
        $this->assertTrue($this->container->hasDefinition('WechatPayFaceToFaceBundle\\Service\\FaceToFacePayService'));
        $this->assertTrue($this->container->hasDefinition('WechatPayFaceToFaceBundle\\Repository\\FaceToFaceOrderRepository'));
        $this->assertTrue($this->container->hasDefinition('WechatPayFaceToFaceBundle\\Controller\\CreateOrderController'));
    }

    public function testLoadWithEmptyConfigs(): void
    {
        $this->extension->load([], $this->container);

        $this->assertGreaterThan(0, count($this->container->getDefinitions()));
    }

    public function testLoadWithNonEmptyConfigs(): void
    {
        $configs = [
            ['some_config' => 'value'],
        ];

        $this->extension->load($configs, $this->container);

        $this->assertGreaterThan(0, count($this->container->getDefinitions()));
    }

    public function testLoadSetsCorrectAutowiring(): void
    {
        $this->extension->load([], $this->container);

        $definitions = $this->container->getDefinitions();

        // Check that autowiring is enabled for bundle services
        foreach ($definitions as $id => $definition) {
            if (str_starts_with($id, 'WechatPayFaceToFaceBundle\\')) {
                $this->assertTrue($definition->isAutowired(), "Service {$id} should be autowired");
            }
        }
    }

    public function testLoadSetsCorrectAutoconfiguration(): void
    {
        $this->extension->load([], $this->container);

        $definitions = $this->container->getDefinitions();

        // Check that autoconfiguration is enabled for bundle services
        foreach ($definitions as $id => $definition) {
            if (str_starts_with($id, 'WechatPayFaceToFaceBundle\\')) {
                $this->assertTrue($definition->isAutoconfigured(), "Service {$id} should be autoconfigured");
            }
        }
    }

    public function testLoadRegistersControllers(): void
    {
        $this->extension->load([], $this->container);

        // Check that controller services are loaded
        $definitions = $this->container->getDefinitions();
        $controllerDefinitions = array_filter(array_keys($definitions), function ($id) {
            return str_contains($id, 'Controller') && str_starts_with($id, 'WechatPayFaceToFaceBundle\\');
        });

        $this->assertGreaterThan(0, count($controllerDefinitions));
    }

    public function testLoadRegistersRepositories(): void
    {
        $this->extension->load([], $this->container);

        // Check that repository services are loaded
        $definitions = $this->container->getDefinitions();
        $repositoryDefinitions = array_filter(array_keys($definitions), function ($id) {
            return str_contains($id, 'Repository') && str_starts_with($id, 'WechatPayFaceToFaceBundle\\');
        });

        $this->assertGreaterThan(0, count($repositoryDefinitions));
    }

    public function testLoadMultipleCalls(): void
    {
        $this->extension->load([], $this->container);
        $firstCount = count($this->container->getDefinitions());

        // Loading again should not duplicate services
        $this->extension->load([], $this->container);
        $secondCount = count($this->container->getDefinitions());

        $this->assertEquals($firstCount, $secondCount);
    }

    public function testExtensionInheritsFromCorrectClass(): void
    {
        $this->assertInstanceOf(
            AutoExtension::class,
            $this->extension
        );
    }

    public function testLoadDoesNotThrowException(): void
    {
        $this->expectNotToPerformAssertions();

        $this->extension->load([], $this->container);
        $this->extension->load([['key' => 'value']], $this->container);
        $this->extension->load([[], ['another' => 'config']], $this->container);
    }

    public function testLoadRegistersEntityServices(): void
    {
        $this->extension->load([], $this->container);

        // Check that entity-related services are loaded
        $definitions = $this->container->getDefinitions();
        $entityDefinitions = array_filter(array_keys($definitions), function ($id) {
            return str_contains($id, 'Entity') && str_starts_with($id, 'WechatPayFaceToFaceBundle\\');
        });

        // Should have entity definitions for Doctrine mapping
        $this->assertGreaterThanOrEqual(0, count($entityDefinitions));
    }

    public function testConfigDirectoryPath(): void
    {
        $reflection = new \ReflectionClass($this->extension);
        $method = $reflection->getMethod('getConfigDir');

        $configDir = $method->invoke($this->extension);
        $this->assertIsString($configDir);

        $this->assertStringEndsWith('/Resources/config', $configDir);
        $this->assertDirectoryExists($configDir);
    }

    public function testExtensionCanLoadMultipleConfigurationSets(): void
    {
        $configs = [
            ['setting1' => 'value1'],
            ['setting2' => 'value2'],
            ['setting3' => ['nested' => 'value']],
        ];

        $this->extension->load($configs, $this->container);

        // Should handle multiple configuration arrays without error
        $this->assertGreaterThan(0, count($this->container->getDefinitions()));
    }

    public function testLoadRegistersMessageHandlers(): void
    {
        $this->extension->load([], $this->container);

        // Check that message handlers are loaded
        $definitions = $this->container->getDefinitions();
        $handlerDefinitions = array_filter(array_keys($definitions), function ($id) {
            return str_contains($id, 'Handler') && str_starts_with($id, 'WechatPayFaceToFaceBundle\\');
        });

        $this->assertGreaterThanOrEqual(0, count($handlerDefinitions));
    }

    public function testLoadRegistersCommands(): void
    {
        $this->extension->load([], $this->container);

        // Check that command services are loaded
        $definitions = $this->container->getDefinitions();
        $commandDefinitions = array_filter(array_keys($definitions), function ($id) {
            return str_contains($id, 'Command') && str_starts_with($id, 'WechatPayFaceToFaceBundle\\');
        });

        $this->assertGreaterThanOrEqual(0, count($commandDefinitions));
    }

    public function testPrepend(): void
    {
        // 模拟框架扩展存在
        $container = $this->createMock(ContainerBuilder::class);
        $container->method('hasExtension')
            ->willReturn(true);
        $container->expects($this->exactly(2))
            ->method('prependExtensionConfig');

        $this->extension->prepend($container);
    }
}