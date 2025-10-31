<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use WechatPayFaceToFaceBundle\Controller\Admin\DashboardController;

#[CoversClass(DashboardController::class)]
#[RunTestsInSeparateProcesses]
final class DashboardControllerTest extends AbstractWebTestCase
{
    public function testConfigureDashboard(): void
    {
        $controller = new DashboardController();
        $dashboard = $controller->configureDashboard();

        // 验证dashboard配置存在
        $this->assertNotNull($dashboard);
    }

    public function testConfigureMenuItems(): void
    {
        $controller = new DashboardController();
        $menuItems = $controller->configureMenuItems();

        // 验证菜单项数量
        $menuArray = iterator_to_array($menuItems);
        $this->assertCount(2, $menuArray);
    }

    public function testControllerInstantiation(): void
    {
        $controller = new DashboardController();
        $this->assertInstanceOf(DashboardController::class, $controller);
    }

    public function testControllerHasIndexMethod(): void
    {
        $controller = new DashboardController();
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('index'));
    }

    /**
     * 测试不允许的HTTP方法
     */
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();

        // DashboardController 是 EasyAdmin 控制器，测试根路径
        $client->request($method, '/admin');

        // 对于不支持的HTTP方法，应该返回405或者路由不存在
        $this->assertTrue($client->getResponse()->getStatusCode() === 405 || $client->getResponse()->getStatusCode() === 404);
    }
}