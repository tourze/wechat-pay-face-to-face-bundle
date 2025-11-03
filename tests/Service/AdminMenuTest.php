<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use WechatPayFaceToFaceBundle\Service\AdminMenu;

#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // 设置测试环境
    }

    public function testInvokeCreatesWeixinPayMenu(): void
    {
        // 模拟LinkGenerator抛出异常，因为没有Dashboard
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $linkGenerator
            ->expects($this->once())
            ->method('getCurdListPage')
            ->with(\WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder::class)
            ->willThrowException(new \RuntimeException('No dashboard found'));

        // 将mock服务注入到容器中
        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        // 从容器中获取服务实例
        $adminMenu = self::getService(AdminMenu::class);
        $rootItem = $this->createMock(ItemInterface::class);

        // 创建菜单项Mock
        $weixinPayItem = $this->createMock(ItemInterface::class);
        $faceToFaceItem = $this->createMock(ItemInterface::class);

        // 设置根菜单的getChild - 需要模拟多次调用
        $rootItem
            ->method('getChild')
            ->willReturn(
                null, // 第一次调用 getChild('微信支付') 返回 null
                $weixinPayItem // 第二次调用 getChild('微信支付') 返回 $weixinPayItem
            );

        // 设置根菜单只添加微信支付子菜单
        $rootItem
            ->expects($this->once())
            ->method('addChild')
            ->with('微信支付')
            ->willReturn($weixinPayItem);

        // 设置微信支付菜单的Mock
        $weixinPayItem
            ->method('setAttribute')
            ->willReturnSelf();

        // 设置微信支付菜单的getChild - 需要模拟多次调用
        $weixinPayItem
            ->method('getChild')
            ->willReturn(
                null, // 第一次调用 getChild('面对面收款') 返回 null
                $faceToFaceItem // 第二次调用 getChild('面对面收款') 返回 $faceToFaceItem
            );

        $weixinPayItem
            ->expects($this->once())
            ->method('addChild')
            ->with('面对面收款')
            ->willReturn($faceToFaceItem);

        // 设置面对面收款菜单的Mock
        $faceToFaceItem
            ->method('setAttribute')
            ->willReturnSelf();

        // 设置面对面收款菜单的getChild - 总是返回null，触发创建收款订单菜单
        $faceToFaceItem
            ->method('getChild')
            ->willReturn(null);

        // 由于LinkGenerator会抛出异常，不应该添加收款订单菜单
        $faceToFaceItem
            ->expects($this->never())
            ->method('addChild');

        $adminMenu->__invoke($rootItem);

        // 如果没有异常，测试通过
        $this->assertTrue(true);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $adminMenu = self::getService(AdminMenu::class);
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
    }

    public function testInvokeDoesNotThrowExceptionWithoutDashboard(): void
    {
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $linkGenerator
            ->expects($this->once())
            ->method('getCurdListPage')
            ->willThrowException(new \RuntimeException('No dashboard'));
        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        $adminMenu = self::getService(AdminMenu::class);
        $rootItem = $this->createMock(ItemInterface::class);

        $weixinPayItem = $this->createMock(ItemInterface::class);
        $faceToFaceItem = $this->createMock(ItemInterface::class);

        $rootItem->method('getChild')->willReturn(null, $weixinPayItem);
        $rootItem->method('addChild')->willReturn($weixinPayItem);

        $weixinPayItem->method('setAttribute')->willReturnSelf();
        $weixinPayItem->method('getChild')->willReturn(null, $faceToFaceItem);
        $weixinPayItem->method('addChild')->willReturn($faceToFaceItem);

        $faceToFaceItem->method('setAttribute')->willReturnSelf();
        $faceToFaceItem->method('getChild')->willReturn(null);
        $faceToFaceItem->method('addChild')->willReturn($this->createMock(ItemInterface::class));

        // 确保即使没有Dashboard，调用也不会抛出异常
        $adminMenu->__invoke($rootItem);
        $this->assertTrue(true);
    }
}