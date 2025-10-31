<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;
use WechatPayFaceToFaceBundle\Service\FaceToFaceOrderDataPopulator;

#[CoversClass(FaceToFaceOrderDataPopulator::class)]
final class FaceToFaceOrderDataPopulatorTest extends TestCase
{
    private FaceToFaceOrderDataPopulator $populator;

    protected function setUp(): void
    {
        $this->populator = new FaceToFaceOrderDataPopulator();
    }

    public function testPopulateOrderFromBasicData(): void
    {
        $data = [
            'out_trade_no' => 'TEST123456',
            'appid' => 'wx-test-appid',
            'mchid' => 'test-mchid',
            'body' => '测试商品',
            'total_fee' => 100,
        ];

        $order = $this->populator->populateOrderFromData($data);

        $this->assertSame('TEST123456', $order->getOutTradeNo());
        $this->assertSame('wx-test-appid', $order->getAppid());
        $this->assertSame('test-mchid', $order->getMchid());
        $this->assertSame('测试商品', $order->getBody());
        $this->assertSame(100, $order->getTotalFee());
        $this->assertSame('CNY', $order->getCurrency());
    }

    public function testPopulateOrderWithOptionalFields(): void
    {
        $data = [
            'out_trade_no' => 'TEST789012',
            'appid' => 'wx-test-appid',
            'mchid' => 'test-mchid',
            'body' => '测试商品2',
            'total_fee' => 200,
            'openid' => 'test-openid',
            'attach' => 'test-attach',
            'goods_tag' => 'test-goods-tag',
            'limit_pay' => 'no_credit',
            'currency' => 'USD',
        ];

        $order = $this->populator->populateOrderFromData($data);

        $this->assertSame('TEST789012', $order->getOutTradeNo());
        $this->assertSame('wx-test-appid', $order->getAppid());
        $this->assertSame('test-mchid', $order->getMchid());
        $this->assertSame('测试商品2', $order->getBody());
        $this->assertSame(200, $order->getTotalFee());
        $this->assertSame('test-openid', $order->getOpenid());
        $this->assertSame('test-attach', $order->getAttach());
        $this->assertSame('test-goods-tag', $order->getGoodsTag());
        $this->assertSame('no_credit', $order->getLimitPay());
        $this->assertSame('USD', $order->getCurrency());
    }

    public function testPopulateOrderWithExpireTime(): void
    {
        $data = [
            'out_trade_no' => 'TEST345678',
            'appid' => 'wx-test-appid',
            'mchid' => 'test-mchid',
            'body' => '测试商品3',
            'total_fee' => 300,
            'expire_minutes' => 30,
        ];

        $beforeCreation = time();
        $order = $this->populator->populateOrderFromData($data);
        $afterCreation = time();

        $this->assertSame('TEST345678', $order->getOutTradeNo());
        $this->assertNotNull($order->getExpireTime());

        $expectedExpireTime = $beforeCreation + 30 * 60;
        $actualExpireTime = $order->getExpireTime();

        // 允许1秒误差
        $this->assertGreaterThanOrEqual($expectedExpireTime - 1, $actualExpireTime);
        $this->assertLessThanOrEqual($afterCreation + 30 * 60 + 1, $actualExpireTime);
    }

    public function testPopulateOrderWithNullValues(): void
    {
        $data = [
            'out_trade_no' => 'TEST567890',
            'appid' => null,
            'mchid' => null,
            'body' => '测试商品5',
            'total_fee' => 500,
            'openid' => null,
            'attach' => null,
        ];

        $order = $this->populator->populateOrderFromData($data);

        $this->assertSame('TEST567890', $order->getOutTradeNo());
        $this->assertSame('', $order->getAppid()); // null转换为空字符串
        $this->assertSame('', $order->getMchid()); // null转换为空字符串
        $this->assertSame('测试商品5', $order->getBody());
        $this->assertSame(500, $order->getTotalFee());
        $this->assertNull($order->getOpenid()); // 可选字段允许null
        $this->assertNull($order->getAttach()); // 可选字段允许null
    }

    public function testPopulateOrderWithNumericValues(): void
    {
        $data = [
            'out_trade_no' => 123456,
            'appid' => 789,
            'mchid' => 456,
            'body' => 789,
            'total_fee' => '600',
            'openid' => 123,
        ];

        $order = $this->populator->populateOrderFromData($data);

        // out_trade_no、body使用is_string检查，数字类型会被转换为空字符串
        $this->assertSame('', $order->getOutTradeNo());
        $this->assertSame('', $order->getBody());

        // appid、mchid、openid使用getRequiredString/getNullableString方法，数字类型会被转换为字符串
        $this->assertSame('789', $order->getAppid());
        $this->assertSame('456', $order->getMchid());
        $this->assertSame('123', $order->getOpenid());

        // total_fee使用is_numeric检查，字符串数字会被转换为整数
        $this->assertSame(600, $order->getTotalFee());
    }

    public function testPopulateOrderWithBooleanValues(): void
    {
        $data = [
            'out_trade_no' => 'TEST678901',
            'appid' => true,
            'mchid' => false,
            'body' => '测试商品6',
            'total_fee' => 700,
            'openid' => true,
        ];

        $order = $this->populator->populateOrderFromData($data);

        // out_trade_no是字符串，正常设置
        $this->assertSame('TEST678901', $order->getOutTradeNo());

        // appid、mchid、openid使用getRequiredString/getNullableString方法，布尔值会被转换为字符串
        $this->assertSame('true', $order->getAppid());
        $this->assertSame('false', $order->getMchid());
        $this->assertSame('true', $order->getOpenid());

        // body是字符串，正常设置
        $this->assertSame('测试商品6', $order->getBody());

        // total_fee是整数，正常设置
        $this->assertSame(700, $order->getTotalFee());
    }

    public function testPopulateOrderWithUnusualTypes(): void
    {
        $data = [
            'out_trade_no' => 'TEST789012',
            'appid' => ['array'],
            'mchid' => (object)['key' => 'value'],
            'body' => 800, // 数字，但body要求字符串
            'total_fee' => 800,
            'openid' => 'resource', // 使用字符串代替资源避免类型检查问题
        ];

        $order = $this->populator->populateOrderFromData($data);

        // out_trade_no是字符串，正常设置
        $this->assertSame('TEST789012', $order->getOutTradeNo());

        // appid、mchid使用getRequiredString方法，不支持数组和对象，转换为默认值
        $this->assertSame('', $order->getAppid());
        $this->assertSame('', $order->getMchid());

        // body使用is_string检查，数字类型会被转换为空字符串
        $this->assertSame('', $order->getBody());

        // total_fee是整数，正常设置
        $this->assertSame(800, $order->getTotalFee());

        // openid是字符串，正常设置
        $this->assertSame('resource', $order->getOpenid());
    }

    public function testPopulateOrderWithMissingRequiredFields(): void
    {
        $data = [
            'body' => '测试商品8',
            'total_fee' => 900,
        ];

        $order = $this->populator->populateOrderFromData($data);

        $this->assertSame('', $order->getOutTradeNo()); // 缺失字段使用默认值
        $this->assertSame('', $order->getAppid());
        $this->assertSame('', $order->getMchid());
        $this->assertSame('测试商品8', $order->getBody());
        $this->assertSame(900, $order->getTotalFee());
        $this->assertSame('CNY', $order->getCurrency()); // 缺失currency使用默认值
    }

    public function testPopulateOrderWithEmptyData(): void
    {
        $data = [];

        $order = $this->populator->populateOrderFromData($data);

        $this->assertSame('', $order->getOutTradeNo());
        $this->assertSame('', $order->getAppid());
        $this->assertSame('', $order->getMchid());
        $this->assertSame('', $order->getBody());
        $this->assertSame(0, $order->getTotalFee());
        $this->assertSame('CNY', $order->getCurrency());
        $this->assertNull($order->getExpireTime());
        $this->assertNull($order->getOpenid());
        $this->assertNull($order->getAttach());
        $this->assertNull($order->getGoodsTag());
        $this->assertNull($order->getLimitPay());
    }

    /**
     * 直接测试 populateOrderFromData 方法
     */
    public function testPopulateOrderFromData(): void
    {
        $data = [
            'out_trade_no' => 'DIRECT_TEST_123',
            'total_fee' => 150,
            'body' => 'Direct Test Order',
            'openid' => 'test_openid',
            'attach' => 'test_attach',
            'goods_tag' => 'test_goods_tag',
            'limit_pay' => 'test_limit_pay',
        ];

        $order = $this->populator->populateOrderFromData($data);

        $this->assertInstanceOf(FaceToFaceOrder::class, $order);
        $this->assertSame('DIRECT_TEST_123', $order->getOutTradeNo());
        $this->assertSame(150, $order->getTotalFee());
        $this->assertSame('Direct Test Order', $order->getBody());
        $this->assertSame('test_openid', $order->getOpenid());
        $this->assertSame('test_attach', $order->getAttach());
        $this->assertSame('test_goods_tag', $order->getGoodsTag());
        $this->assertSame('test_limit_pay', $order->getLimitPay());
    }
}