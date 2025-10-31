<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum TradeState: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case NOTPAY = 'NOTPAY'; // 未支付
    case SUCCESS = 'SUCCESS'; // 支付成功
    case REFUND = 'REFUND'; // 转入退款
    case NOTPAYNOT = 'NOTPAYNOT'; // 未支付超时已关闭
    case CLOSED = 'CLOSED'; // 已关闭
    case PAYERROR = 'PAYERROR'; // 支付失败
    case USERPAYING = 'USERPAYING'; // 用户支付中

    public function getLabel(): string
    {
        return match ($this) {
            self::NOTPAY => '未支付',
            self::SUCCESS => '支付成功',
            self::REFUND => '转入退款',
            self::NOTPAYNOT => '未支付超时已关闭',
            self::CLOSED => '已关闭',
            self::PAYERROR => '支付失败',
            self::USERPAYING => '用户支付中',
        };
    }

    /**
     * 获取枚举值
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * 获取徽章类型（用于 UI 显示）
     */
    public function getBadge(): string
    {
        return match ($this) {
            self::NOTPAY => 'secondary',
            self::USERPAYING => 'info',
            self::SUCCESS => 'success',
            self::REFUND => 'warning',
            self::CLOSED, self::NOTPAYNOT, self::PAYERROR => 'danger',
        };
    }

    /**
     * 检查是否为最终状态
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::SUCCESS, self::REFUND, self::CLOSED, self::PAYERROR => true,
            self::NOTPAY, self::NOTPAYNOT, self::USERPAYING => false,
        };
    }

    /**
     * 检查是否为成功状态
     */
    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * 检查是否为失败状态
     */
    public function isFailed(): bool
    {
        return match ($this) {
            self::CLOSED, self::PAYERROR => true,
            default => false,
        };
    }
}
