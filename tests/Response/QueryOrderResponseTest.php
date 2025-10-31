<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Response;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WechatPayFaceToFaceBundle\Response\QueryOrderResponse;

#[CoversClass(QueryOrderResponse::class)]
final class QueryOrderResponseTest extends TestCase
{
    public function testResponseWithValidData(): void
    {
        $data = [
            'out_trade_no' => 'TEST123456',
            'trade_state' => 'SUCCESS',
            'trade_state_desc' => '支付成功',
            'transaction_id' => 'wx1234567890',
            'bank_type' => 'CFT',
            'success_time' => '20231201120000',
            'pay_type' => 'NATIVE',
            'time_end' => 1701427200,
            'errmsg' => 'OK',
            'errcode' => '0'
        ];

        $response = new QueryOrderResponse($data);

        $this->assertSame('TEST123456', $response->getOutTradeNo());
        $this->assertSame('SUCCESS', $response->getTradeState());
        $this->assertSame('支付成功', $response->getTradeStateDesc());
        $this->assertSame('wx1234567890', $response->getTransactionId());
        $this->assertSame('CFT', $response->getBankType());
        $this->assertSame('20231201120000', $response->getSuccessTime());
        $this->assertSame('NATIVE', $response->getPayType());
        $this->assertSame(1701427200, $response->getTimeEnd());
        $this->assertSame('OK', $response->getErrMsg());
        $this->assertSame('0', $response->getErrCode());
    }

    public function testResponseWithMinimalData(): void
    {
        $data = [
            'trade_state' => 'NOTPAY'
        ];

        $response = new QueryOrderResponse($data);

        $this->assertSame('NOTPAY', $response->getTradeState());
        $this->assertNull($response->getOutTradeNo());
        $this->assertNull($response->getTradeStateDesc());
        $this->assertNull($response->getTransactionId());
        $this->assertNull($response->getBankType());
        $this->assertNull($response->getSuccessTime());
        $this->assertNull($response->getPayType());
        $this->assertNull($response->getTimeEnd());
        $this->assertNull($response->getErrMsg());
        $this->assertNull($response->getErrCode());
    }

    public function testResponseWithInvalidDataTypes(): void
    {
        $data = [
            'trade_state' => 123, // 非字符串
            'out_trade_no' => [], // 非字符串
            'time_end' => 'invalid', // 非数字
            'errmsg' => true, // 非字符串
            'errcode' => null // 非字符串
        ];

        $response = new QueryOrderResponse($data);

        $this->assertSame('', $response->getTradeState());
        $this->assertNull($response->getOutTradeNo());
        $this->assertNull($response->getTimeEnd());
        $this->assertNull($response->getErrMsg());
        $this->assertNull($response->getErrCode());
    }

    public function testResponseWithNumericTimeEnd(): void
    {
        $data = [
            'trade_state' => 'SUCCESS',
            'time_end' => '1701427200' // 字符串数字
        ];

        $response = new QueryOrderResponse($data);

        $this->assertSame(1701427200, $response->getTimeEnd());
    }

    public function testResponseWithInvalidTimeEnd(): void
    {
        $data = [
            'trade_state' => 'SUCCESS',
            'time_end' => 'invalid_number'
        ];

        $response = new QueryOrderResponse($data);

        $this->assertNull($response->getTimeEnd());
    }

    public function testIsPaidReturnsTrueForSuccess(): void
    {
        $data = ['trade_state' => 'SUCCESS'];
        $response = new QueryOrderResponse($data);

        $this->assertTrue($response->isPaid());
    }

    public function testIsPaidReturnsFalseForOtherStates(): void
    {
        $states = ['NOTPAY', 'CLOSED', 'PAYERROR', 'NOTPAYNOT', 'REFUND'];

        foreach ($states as $state) {
            $data = ['trade_state' => $state];
            $response = new QueryOrderResponse($data);
            $this->assertFalse($response->isPaid(), "State {$state} should not be paid");
        }
    }

    public function testIsFailedReturnsTrueForFailedStates(): void
    {
        $failedStates = ['CLOSED', 'PAYERROR', 'NOTPAYNOT'];

        foreach ($failedStates as $state) {
            $data = ['trade_state' => $state];
            $response = new QueryOrderResponse($data);
            $this->assertTrue($response->isFailed(), "State {$state} should be failed");
        }
    }

    public function testIsFailedReturnsFalseForNonFailedStates(): void
    {
        $nonFailedStates = ['SUCCESS', 'NOTPAY', 'REFUND'];

        foreach ($nonFailedStates as $state) {
            $data = ['trade_state' => $state];
            $response = new QueryOrderResponse($data);
            $this->assertFalse($response->isFailed(), "State {$state} should not be failed");
        }
    }

    public function testIsNotPaidReturnsTrueForNotPay(): void
    {
        $data = ['trade_state' => 'NOTPAY'];
        $response = new QueryOrderResponse($data);

        $this->assertTrue($response->isNotPaid());
    }

    public function testIsNotPaidReturnsFalseForOtherStates(): void
    {
        $states = ['SUCCESS', 'CLOSED', 'PAYERROR', 'NOTPAYNOT', 'REFUND'];

        foreach ($states as $state) {
            $data = ['trade_state' => $state];
            $response = new QueryOrderResponse($data);
            $this->assertFalse($response->isNotPaid(), "State {$state} should not be not paid");
        }
    }

    public function testIsFinalStateReturnsTrueForSuccess(): void
    {
        $data = ['trade_state' => 'SUCCESS'];
        $response = new QueryOrderResponse($data);

        $this->assertTrue($response->isFinalState());
    }

    public function testIsFinalStateReturnsTrueForFailedStates(): void
    {
        $failedStates = ['CLOSED', 'PAYERROR', 'NOTPAYNOT'];

        foreach ($failedStates as $state) {
            $data = ['trade_state' => $state];
            $response = new QueryOrderResponse($data);
            $this->assertTrue($response->isFinalState(), "State {$state} should be final");
        }
    }

    public function testIsFinalStateReturnsTrueForRefund(): void
    {
        $data = ['trade_state' => 'REFUND'];
        $response = new QueryOrderResponse($data);

        $this->assertTrue($response->isFinalState());
    }

    public function testIsFinalStateReturnsFalseForNonFinalStates(): void
    {
        $data = ['trade_state' => 'NOTPAY'];
        $response = new QueryOrderResponse($data);

        $this->assertFalse($response->isFinalState());
    }

    public function testResponseWithEmptyData(): void
    {
        $data = [];
        $response = new QueryOrderResponse($data);

        $this->assertSame('', $response->getTradeState());
        $this->assertNull($response->getOutTradeNo());
        $this->assertNull($response->getTradeStateDesc());
        $this->assertNull($response->getTransactionId());
        $this->assertNull($response->getBankType());
        $this->assertNull($response->getSuccessTime());
        $this->assertNull($response->getPayType());
        $this->assertNull($response->getTimeEnd());
        $this->assertNull($response->getErrMsg());
        $this->assertNull($response->getErrCode());

        $this->assertFalse($response->isPaid());
        $this->assertFalse($response->isFailed());
        $this->assertFalse($response->isNotPaid());
        $this->assertFalse($response->isFinalState());
    }
}