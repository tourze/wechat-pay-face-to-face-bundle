<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;

#[CoversClass(FaceToFaceOrder::class)]
final class FaceToFaceOrderTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new FaceToFaceOrder();
    }

    public static function propertiesProvider(): iterable
    {
        yield ['outTradeNo', 'TEST123'];
        yield ['appid', 'wx-appid'];
        yield ['mchid', 'mch-123'];
        yield ['totalFee', 100];
        yield ['body', '测试商品'];
        yield ['tradeState', 'SUCCESS'];
        yield ['transactionId', 'transaction-001'];
        yield ['prepayId', 'prepay-001'];
    }

    public function testCreateOrder(): void
    {
        $order = new FaceToFaceOrder();
        
        $this->assertNull($order->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $order->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $order->getUpdatedAt());
    }

    public function testOrderSettersAndGetters(): void
    {
        $order = new FaceToFaceOrder();
        
        $order->setOutTradeNo('TEST123456789');
        $order->setAppid('wx1234567890abcdef');
        $order->setMchid('1900000100');
        $order->setTotalFee(100);
        $order->setBody('测试商品');
        $order->setOpenid('o1234567890abcdef');
        
        $this->assertSame('TEST123456789', $order->getOutTradeNo());
        $this->assertSame('wx1234567890abcdef', $order->getAppid());
        $this->assertSame('1900000100', $order->getMchid());
        $this->assertSame(100, $order->getTotalFee());
        $this->assertSame('测试商品', $order->getBody());
        $this->assertSame('o1234567890abcdef', $order->getOpenid());
        $this->assertSame('CNY', $order->getCurrency());
        $this->assertSame('NOTPAY', $order->getTradeState());
    }

    public function testOrderTimestampUpdate(): void
    {
        $order = new FaceToFaceOrder();
        $originalUpdatedAt = $order->getUpdatedAt();
        
        usleep(1000);
        $order->updateTimestamp();
        
        $this->assertGreaterThanOrEqual($originalUpdatedAt?->getTimestamp() ?? 0, $order->getUpdatedAt()?->getTimestamp() ?? 0);
        $this->assertNotSame($originalUpdatedAt, $order->getUpdatedAt());
    }

    public function testOrderNullableFields(): void
    {
        $order = new FaceToFaceOrder();
        
        $this->assertNull($order->getOpenid());
        $this->assertNull($order->getCodeUrl());
        $this->assertNull($order->getPrepayId());
        $this->assertNull($order->getTransactionId());
        $this->assertNull($order->getTradeStateDesc());
        $this->assertNull($order->getBankType());
        $this->assertNull($order->getSuccessTime());
        $this->assertNull($order->getPayType());
        $this->assertNull($order->getSubAppid());
        $this->assertNull($order->getSubMchid());
        $this->assertNull($order->getErrMsg());
        $this->assertNull($order->getErrCode());
        $this->assertNull($order->getAttach());
        $this->assertNull($order->getGoodsTag());
        $this->assertNull($order->getLimitPay());
        $this->assertNull($order->getPromotionInfo());
        $this->assertNull($order->getExpireTime());
        $this->assertNull($order->getTimeEnd());
        $this->assertNull($order->getUserId());
    }
}
