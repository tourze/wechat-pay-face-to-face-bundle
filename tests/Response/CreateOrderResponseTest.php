<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Response;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WechatPayFaceToFaceBundle\Response\CreateOrderResponse;

#[CoversClass(CreateOrderResponse::class)]
final class CreateOrderResponseTest extends TestCase
{
    public function testResponseWithValidData(): void
    {
        $data = [
            'code_url' => 'weixin://wxpay/bizpayurl?pr=xxxx',
            'prepay_id' => 'prepay_123456',
            'errmsg' => 'OK',
            'errcode' => '0'
        ];

        $response = new CreateOrderResponse($data);

        $this->assertSame('weixin://wxpay/bizpayurl?pr=xxxx', $response->getCodeUrl());
        $this->assertSame('prepay_123456', $response->getPrepayId());
        $this->assertSame('OK', $response->getErrMsg());
        $this->assertSame('0', $response->getErrCode());
        $this->assertTrue($response->isSuccess());
    }

    public function testResponseWithMinimalData(): void
    {
        $data = [
            'code_url' => 'weixin://wxpay/bizpayurl?pr=yyyy',
            'prepay_id' => 'prepay_789012'
        ];

        $response = new CreateOrderResponse($data);

        $this->assertSame('weixin://wxpay/bizpayurl?pr=yyyy', $response->getCodeUrl());
        $this->assertSame('prepay_789012', $response->getPrepayId());
        $this->assertNull($response->getErrMsg());
        $this->assertNull($response->getErrCode());
        $this->assertTrue($response->isSuccess());
    }

    public function testResponseWithEmptyCodeUrl(): void
    {
        $data = [
            'code_url' => '',
            'prepay_id' => 'prepay_123456'
        ];

        $response = new CreateOrderResponse($data);

        $this->assertSame('', $response->getCodeUrl());
        $this->assertSame('prepay_123456', $response->getPrepayId());
        $this->assertFalse($response->isSuccess());
    }

    public function testResponseWithEmptyPrepayId(): void
    {
        $data = [
            'code_url' => 'weixin://wxpay/bizpayurl?pr=xxxx',
            'prepay_id' => ''
        ];

        $response = new CreateOrderResponse($data);

        $this->assertSame('weixin://wxpay/bizpayurl?pr=xxxx', $response->getCodeUrl());
        $this->assertSame('', $response->getPrepayId());
        $this->assertFalse($response->isSuccess());
    }

    public function testResponseWithInvalidDataTypes(): void
    {
        $data = [
            'code_url' => 123, // 非字符串
            'prepay_id' => [], // 非字符串
            'errmsg' => true, // 非字符串
            'errcode' => null // 非字符串
        ];

        $response = new CreateOrderResponse($data);

        $this->assertSame('', $response->getCodeUrl());
        $this->assertSame('', $response->getPrepayId());
        $this->assertNull($response->getErrMsg());
        $this->assertNull($response->getErrCode());
        $this->assertFalse($response->isSuccess());
    }

    public function testResponseWithMissingFields(): void
    {
        $data = [];

        $response = new CreateOrderResponse($data);

        $this->assertSame('', $response->getCodeUrl());
        $this->assertSame('', $response->getPrepayId());
        $this->assertNull($response->getErrMsg());
        $this->assertNull($response->getErrCode());
        $this->assertFalse($response->isSuccess());
    }

    public function testResponseWithPartialData(): void
    {
        $data = [
            'code_url' => 'weixin://wxpay/bizpayurl?pr=partial',
            // 缺少 prepay_id
            'errmsg' => 'Partial error'
        ];

        $response = new CreateOrderResponse($data);

        $this->assertSame('weixin://wxpay/bizpayurl?pr=partial', $response->getCodeUrl());
        $this->assertSame('', $response->getPrepayId());
        $this->assertSame('Partial error', $response->getErrMsg());
        $this->assertNull($response->getErrCode());
        $this->assertFalse($response->isSuccess());
    }

    public function testIsSuccessWithValidValues(): void
    {
        $data = [
            'code_url' => 'valid_url',
            'prepay_id' => 'valid_prepay'
        ];

        $response = new CreateOrderResponse($data);
        $this->assertTrue($response->isSuccess());
    }

    public function testIsSuccessWithEmptyCodeUrl(): void
    {
        $data = [
            'code_url' => '',
            'prepay_id' => 'valid_prepay'
        ];

        $response = new CreateOrderResponse($data);
        $this->assertFalse($response->isSuccess());
    }

    public function testIsSuccessWithEmptyPrepayId(): void
    {
        $data = [
            'code_url' => 'valid_url',
            'prepay_id' => ''
        ];

        $response = new CreateOrderResponse($data);
        $this->assertFalse($response->isSuccess());
    }

    public function testIsSuccessWithBothEmpty(): void
    {
        $data = [
            'code_url' => '',
            'prepay_id' => ''
        ];

        $response = new CreateOrderResponse($data);
        $this->assertFalse($response->isSuccess());
    }
}