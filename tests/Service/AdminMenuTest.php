<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
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
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        // 将mock服务注入到容器中
        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        // 从容器中获取服务实例
        $adminMenu = self::getService(AdminMenu::class);
        $rootItem = $this->createMock(ItemInterface::class);

        // 创建菜单项Mock
        $weixinPayItem = $this->createMock(ItemInterface::class);
        $faceToFaceItem = $this->createMock(ItemInterface::class);
        $orderItem = $this->createMock(ItemInterface::class);

        // 设置根菜单的getChild：第一次返回null触发创建，之后返回对应菜单
        $rootItem
            ->method('getChild')
            ->willReturnMap([
                ['微信支付', null], // 第一次返回null，触发创建
                ['微信支付', $weixinPayItem], // 第二次返回创建的菜单
            ]);

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

        $weixinPayItem
            ->method('getChild')
            ->willReturnMap([
                ['面对面收款', null], // 第一次返回null，触发创建
                ['面对面收款', $faceToFaceItem], // 第二次返回创建的菜单
            ]);

        $weixinPayItem
            ->expects($this->once())
            ->method('addChild')
            ->with('面对面收款')
            ->willReturn($faceToFaceItem);

        // 设置面对面收款菜单的Mock
        $faceToFaceItem
            ->method('setAttribute')
            ->willReturnSelf();

        $faceToFaceItem
            ->method('getChild')
            ->willReturn(null); // 返回null触发创建收款订单菜单

        $faceToFaceItem
            ->expects($this->once())
            ->method('addChild')
            ->with('收款订单')
            ->willReturn($orderItem);

        // 设置收款订单菜单的Mock
        $orderItem
            ->method('setAttribute')
            ->willReturnSelf();

        // 设置链接生成器 - 这是关键，必须被调用
        $linkGenerator
            ->expects($this->once())
            ->method('getCurdListPage')
            ->with(\WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder::class)
            ->willReturn('/admin/wechat-pay-face-to-face/face-to-face-order');

        $orderItem
            ->expects($this->once())
            ->method('setUri')
            ->with('/admin/wechat-pay-face-to-face/face-to-face-order')
            ->willReturnSelf();

        $adminMenu->__invoke($rootItem);

        // 如果没有异常，测试通过
        $this->assertTrue(true);
    }

    public function testInvokeWithExistingWeixinPayMenu(): void
    {
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        // 将mock服务注入到容器中
        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        // 从容器中获取服务实例
        $adminMenu = self::getService(AdminMenu::class);

        // 设置根菜单已有微信支付子菜单
        $weixinPayItem = $this->createMock(ItemInterface::class);
        $rootItem = $this->createMock(ItemInterface::class);
        $rootItem
            ->expects($this->once())
            ->method('getChild')
            ->with('微信支付')
            ->willReturn($weixinPayItem);

        // 不应该添加新的微信支付菜单
        $rootItem
            ->expects($this->never())
            ->method('addChild');

        // 设置微信支付菜单没有面对面收款子菜单
        $weixinPayItem
            ->expects($this->once())
            ->method('getChild')
            ->with('面对面收款')
            ->willReturn(null);

        // 设置微信支付菜单添加面对面收款子菜单
        $faceToFaceItem = $this->createMock(ItemInterface::class);
        $weixinPayItem
            ->expects($this->once())
            ->method('addChild')
            ->with('面对面收款')
            ->willReturn($faceToFaceItem);

        $faceToFaceItem
            ->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-qrcode');

        // 设置面对面收款菜单没有收款订单子菜单
        $faceToFaceItem
            ->expects($this->once())
            ->method('getChild')
            ->with('收款订单')
            ->willReturn(null);

        // 设置面对面收款菜单添加收款订单子菜单
        $orderItem = $this->createMock(ItemInterface::class);
        $faceToFaceItem
            ->expects($this->once())
            ->method('addChild')
            ->with('收款订单')
            ->willReturn($orderItem);

        $orderItem
            ->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-receipt');

        // 设置链接生成器返回CRUD列表页面URL
        $linkGenerator
            ->expects($this->once())
            ->method('getCurdListPage')
            ->with(\WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder::class)
            ->willReturn('/admin/wechat-pay-face-to-face/face-to-face-order');

        $orderItem
            ->expects($this->once())
            ->method('setUri')
            ->with('/admin/wechat-pay-face-to-face/face-to-face-order');

        $adminMenu->__invoke($rootItem);
    }

    public function testInvokeWithExistingFaceToFaceMenu(): void
    {
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        // 将mock服务注入到容器中
        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        // 从容器中获取服务实例
        $adminMenu = self::getService(AdminMenu::class);

        // 设置根菜单已有微信支付子菜单
        $weixinPayItem = $this->createMock(ItemInterface::class);
        $rootItem = $this->createMock(ItemInterface::class);
        $rootItem
            ->expects($this->once())
            ->method('getChild')
            ->with('微信支付')
            ->willReturn($weixinPayItem);

        // 设置微信支付菜单已有面对面收款子菜单
        $faceToFaceItem = $this->createMock(ItemInterface::class);
        $weixinPayItem
            ->expects($this->once())
            ->method('getChild')
            ->with('面对面收款')
            ->willReturn($faceToFaceItem);

        // 不应该添加新的面对面收款菜单
        $weixinPayItem
            ->expects($this->never())
            ->method('addChild');

        // 设置面对面收款菜单没有收款订单子菜单
        $faceToFaceItem
            ->expects($this->once())
            ->method('getChild')
            ->with('收款订单')
            ->willReturn(null);

        // 设置面对面收款菜单添加收款订单子菜单
        $orderItem = $this->createMock(ItemInterface::class);
        $faceToFaceItem
            ->expects($this->once())
            ->method('addChild')
            ->with('收款订单')
            ->willReturn($orderItem);

        $orderItem
            ->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-receipt');

        // 设置链接生成器返回CRUD列表页面URL
        $linkGenerator
            ->expects($this->once())
            ->method('getCurdListPage')
            ->with(\WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder::class)
            ->willReturn('/admin/wechat-pay-face-to-face/face-to-face-order');

        $orderItem
            ->expects($this->once())
            ->method('setUri')
            ->with('/admin/wechat-pay-face-to-face/face-to-face-order');

        $adminMenu->__invoke($rootItem);
    }

    public function testInvokeWithExistingOrderMenu(): void
    {
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        // 将mock服务注入到容器中
        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        // 从容器中获取服务实例
        $adminMenu = self::getService(AdminMenu::class);

        // 设置根菜单已有微信支付子菜单
        $weixinPayItem = $this->createMock(ItemInterface::class);
        $rootItem = $this->createMock(ItemInterface::class);
        $rootItem
            ->expects($this->once())
            ->method('getChild')
            ->with('微信支付')
            ->willReturn($weixinPayItem);

        // 设置微信支付菜单已有面对面收款子菜单
        $faceToFaceItem = $this->createMock(ItemInterface::class);
        $weixinPayItem
            ->expects($this->once())
            ->method('getChild')
            ->with('面对面收款')
            ->willReturn($faceToFaceItem);

        // 设置面对面收款菜单已有收款订单子菜单
        $orderItem = $this->createMock(ItemInterface::class);
        $faceToFaceItem
            ->expects($this->once())
            ->method('getChild')
            ->with('收款订单')
            ->willReturn($orderItem);

        // 不应该添加新的收款订单菜单
        $faceToFaceItem
            ->expects($this->never())
            ->method('addChild');

        // 不应该调用链接生成器，因为菜单已存在
        $linkGenerator
            ->expects($this->never())
            ->method('getCurdListPage');

        $adminMenu->__invoke($rootItem);
    }

    public function testInvokeHandlesNullWeixinPayMenu(): void
    {
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        // 将mock服务注入到容器中
        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        // 从容器中获取服务实例
        $adminMenu = self::getService(AdminMenu::class);

        // 设置根菜单没有微信支付子菜单
        $rootItem = $this->createMock(ItemInterface::class);
        $rootItem
            ->expects($this->once())
            ->method('getChild')
            ->with('微信支付')
            ->willReturn(null);

        // 设置根菜单添加微信支付子菜单，但返回null（模拟失败情况）
        $rootItem
            ->expects($this->once())
            ->method('addChild')
            ->with('微信支付')
            ->willReturn(null);

        // 不应该继续执行后续逻辑
        $rootItem
            ->expects($this->never())
            ->method('getChild')
            ->with('面对面收款');

        $adminMenu->__invoke($rootItem);
    }

    public function testInvokeHandlesNullFaceToFaceMenu(): void
    {
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        // 将mock服务注入到容器中
        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        // 从容器中获取服务实例
        $adminMenu = self::getService(AdminMenu::class);

        // 设置根菜单已有微信支付子菜单
        $weixinPayItem = $this->createMock(ItemInterface::class);
        $rootItem = $this->createMock(ItemInterface::class);
        $rootItem
            ->expects($this->once())
            ->method('getChild')
            ->with('微信支付')
            ->willReturn($weixinPayItem);

        // 设置微信支付菜单没有面对面收款子菜单
        $weixinPayItem
            ->expects($this->once())
            ->method('getChild')
            ->with('面对面收款')
            ->willReturn(null);

        // 设置微信支付菜单添加面对面收款子菜单，但返回null（模拟失败情况）
        $weixinPayItem
            ->expects($this->once())
            ->method('addChild')
            ->with('面对面收款')
            ->willReturn(null);

        // 不应该继续执行后续逻辑
        $weixinPayItem
            ->expects($this->never())
            ->method('getChild')
            ->with('收款订单');

        $adminMenu->__invoke($rootItem);
    }
}