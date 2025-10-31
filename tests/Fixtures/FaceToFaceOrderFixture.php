<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;
use WechatPayFaceToFaceBundle\Enum\TradeState;

final class FaceToFaceOrderFixture extends Fixture implements OrderedFixtureInterface
{
    public function getOrder(): int
    {
        return 0;
    }

    public function load(ObjectManager $manager): void
    {
        $repository = $manager->getRepository(FaceToFaceOrder::class);

        if (method_exists($repository, 'count') && $repository->count([]) > 0) {
            return;
        }

        $order = new FaceToFaceOrder();
        $order->setOutTradeNo('FIXTURE-' . uniqid('', true));
        $order->setAppid('wx-fixture-appid');
        $order->setMchid('fixture-mchid');
        $order->setTotalFee(1000);
        $order->setBody('测试订单');
        $order->setOpenid('openid-fixture');
        $order->setPrepayId('prepay-fixture');
        $order->setTransactionId('transaction-fixture');
        $order->setTradeState(TradeState::SUCCESS->value);

        $manager->persist($order);
        $manager->flush();
    }
}
