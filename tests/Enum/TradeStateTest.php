<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use WechatPayFaceToFaceBundle\Enum\TradeState;

#[CoversClass(TradeState::class)]
final class TradeStateTest extends AbstractEnumTestCase
{
    public function testTradeStateValues(): void
    {
        $this->assertSame('NOTPAY', TradeState::NOTPAY->name);
        $this->assertSame('SUCCESS', TradeState::SUCCESS->name);
        $this->assertSame('REFUND', TradeState::REFUND->name);
        $this->assertSame('NOTPAYNOT', TradeState::NOTPAYNOT->name);
        $this->assertSame('CLOSED', TradeState::CLOSED->name);
        $this->assertSame('PAYERROR', TradeState::PAYERROR->name);
        $this->assertSame('USERPAYING', TradeState::USERPAYING->name);
    }

    public function testTradeStateLabels(): void
    {
        $this->assertSame('未支付', TradeState::NOTPAY->getLabel());
        $this->assertSame('支付成功', TradeState::SUCCESS->getLabel());
        $this->assertSame('转入退款', TradeState::REFUND->getLabel());
        $this->assertSame('未支付超时已关闭', TradeState::NOTPAYNOT->getLabel());
        $this->assertSame('已关闭', TradeState::CLOSED->getLabel());
        $this->assertSame('支付失败', TradeState::PAYERROR->getLabel());
        $this->assertSame('用户支付中', TradeState::USERPAYING->getLabel());
    }

    public function testTradeStateBadges(): void
    {
        $this->assertSame('secondary', TradeState::NOTPAY->getBadge());
        $this->assertSame('info', TradeState::USERPAYING->getBadge());
        $this->assertSame('success', TradeState::SUCCESS->getBadge());
        $this->assertSame('warning', TradeState::REFUND->getBadge());
        $this->assertSame('danger', TradeState::CLOSED->getBadge());
        $this->assertSame('danger', TradeState::NOTPAYNOT->getBadge());
        $this->assertSame('danger', TradeState::PAYERROR->getBadge());
    }

    public function testIsFinal(): void
    {
        $this->assertFalse(TradeState::NOTPAY->isFinal());
        $this->assertTrue(TradeState::SUCCESS->isFinal());
        $this->assertTrue(TradeState::REFUND->isFinal());
        $this->assertFalse(TradeState::NOTPAYNOT->isFinal());
        $this->assertTrue(TradeState::CLOSED->isFinal());
        $this->assertTrue(TradeState::PAYERROR->isFinal());
        $this->assertFalse(TradeState::USERPAYING->isFinal());
    }

    public function testIsSuccess(): void
    {
        $this->assertFalse(TradeState::NOTPAY->isSuccess());
        $this->assertTrue(TradeState::SUCCESS->isSuccess());
        $this->assertFalse(TradeState::REFUND->isSuccess());
        $this->assertFalse(TradeState::NOTPAYNOT->isSuccess());
        $this->assertFalse(TradeState::CLOSED->isSuccess());
        $this->assertFalse(TradeState::PAYERROR->isSuccess());
        $this->assertFalse(TradeState::USERPAYING->isSuccess());
    }

    public function testIsFailed(): void
    {
        $this->assertFalse(TradeState::NOTPAY->isFailed());
        $this->assertFalse(TradeState::SUCCESS->isFailed());
        $this->assertFalse(TradeState::REFUND->isFailed());
        $this->assertFalse(TradeState::NOTPAYNOT->isFailed());
        $this->assertTrue(TradeState::CLOSED->isFailed());
        $this->assertTrue(TradeState::PAYERROR->isFailed());
        $this->assertFalse(TradeState::USERPAYING->isFailed());
    }

    public function testToArray(): void
    {
        $this->assertSame(['value' => 'NOTPAY', 'label' => '未支付'], TradeState::NOTPAY->toArray());
        $this->assertSame(['value' => 'SUCCESS', 'label' => '支付成功'], TradeState::SUCCESS->toArray());
        $this->assertSame(['value' => 'REFUND', 'label' => '转入退款'], TradeState::REFUND->toArray());
        $this->assertSame(['value' => 'NOTPAYNOT', 'label' => '未支付超时已关闭'], TradeState::NOTPAYNOT->toArray());
        $this->assertSame(['value' => 'CLOSED', 'label' => '已关闭'], TradeState::CLOSED->toArray());
        $this->assertSame(['value' => 'PAYERROR', 'label' => '支付失败'], TradeState::PAYERROR->toArray());
        $this->assertSame(['value' => 'USERPAYING', 'label' => '用户支付中'], TradeState::USERPAYING->toArray());
    }

    /**
     * @param array<string, mixed> $expected
     */
    #[TestWith([TradeState::NOTPAY, ['value' => 'NOTPAY', 'label' => '未支付']], 'NOTPAY')]
    #[TestWith([TradeState::SUCCESS, ['value' => 'SUCCESS', 'label' => '支付成功']], 'SUCCESS')]
    #[TestWith([TradeState::REFUND, ['value' => 'REFUND', 'label' => '转入退款']], 'REFUND')]
    #[TestWith([TradeState::NOTPAYNOT, ['value' => 'NOTPAYNOT', 'label' => '未支付超时已关闭']], 'NOTPAYNOT')]
    #[TestWith([TradeState::CLOSED, ['value' => 'CLOSED', 'label' => '已关闭']], 'CLOSED')]
    #[TestWith([TradeState::PAYERROR, ['value' => 'PAYERROR', 'label' => '支付失败']], 'PAYERROR')]
    #[TestWith([TradeState::USERPAYING, ['value' => 'USERPAYING', 'label' => '用户支付中']], 'USERPAYING')]
    public function testToArrayWithProvider(TradeState $tradeState, array $expected): void
    {
        $this->assertSame($expected, $tradeState->toArray());
    }
}
