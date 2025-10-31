<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;

class FaceToFaceOrderFixtures extends Fixture
{
    public const FACE_TO_FACE_ORDER_REFERENCE = 'face-to-face-order-1';

    public function load(ObjectManager $manager): void
    {
        // 创建测试订单数据
        $order = new FaceToFaceOrder();
        $order->setOutTradeNo('TEST_ORDER_' . uniqid());
        $order->setAppid('wx1234567890abcdef');
        $order->setMchid('1234567890');
        $order->setTotalFee(100); // 1元
        $order->setBody('测试商品');
        $order->setOpenid('test_openid_123');
        $order->setTradeState('NOTPAY');

        $manager->persist($order);
        $manager->flush();

        // 添加引用供其他Fixtures使用
        $this->addReference(self::FACE_TO_FACE_ORDER_REFERENCE, $order);
    }
}