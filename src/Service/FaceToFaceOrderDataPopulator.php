<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Service;

use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;

/**
 * 面对面支付订单数据填充器
 */
class FaceToFaceOrderDataPopulator
{
    /**
     * 从数据填充订单实体
     *
     * @param array<mixed> $data
     */
    public function populateOrderFromData(array $data): FaceToFaceOrder
    {
        $order = new FaceToFaceOrder();

        $this->setCoreOrderFields($order, $data);
        $this->setRequiredOrderFields($order, $data);
        $this->setOptionalOrderFields($order, $data);
        $this->setOrderExpireTime($order, $data);

        return $order;
    }

    /**
     * 设置核心订单字段
     *
     * @param array<mixed> $data
     */
    private function setCoreOrderFields(FaceToFaceOrder $order, array $data): void
    {
        $outTradeNo = $this->getRequiredString($data, 'out_trade_no', '');
        $body = $this->getRequiredString($data, 'body', '');
        $totalFee = isset($data['total_fee']) && is_numeric($data['total_fee']) ? (int) $data['total_fee'] : 0;

        $order->setOutTradeNo($outTradeNo);
        $order->setTotalFee($totalFee);
        $order->setBody($body);
    }

    /**
     * 设置必填订单字段
     *
     * @param array<mixed> $data
     */
    private function setRequiredOrderFields(FaceToFaceOrder $order, array $data): void
    {
        $order->setAppid($this->getRequiredString($data, 'appid', ''));
        $order->setMchid($this->getRequiredString($data, 'mchid', ''));
        $order->setCurrency($this->getRequiredString($data, 'currency', 'CNY'));
    }

    /**
     * 设置可选订单字段
     *
     * @param array<mixed> $data
     */
    private function setOptionalOrderFields(FaceToFaceOrder $order, array $data): void
    {
        $order->setOpenid($this->getNullableString($data, 'openid'));
        $order->setAttach($this->getNullableString($data, 'attach'));
        $order->setGoodsTag($this->getNullableString($data, 'goods_tag'));
        $order->setLimitPay($this->getNullableString($data, 'limit_pay'));
    }

    /**
     * 设置订单过期时间
     *
     * @param array<mixed> $data
     */
    private function setOrderExpireTime(FaceToFaceOrder $order, array $data): void
    {
        if (isset($data['expire_minutes'])) {
            $expireMinutes = is_numeric($data['expire_minutes']) ? (int) $data['expire_minutes'] : 0;
            $order->setExpireTime(time() + $expireMinutes * 60);
        }
    }

    /**
     * 类型安全地获取必填字符串字段（Entity定义为string，不允许null）
     *
     * @param array<mixed> $data
     */
    private function getRequiredString(array $data, string $key, string $default = ''): string
    {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        $value = $data[$key];

        // 处理null值（虽然Entity不允许null，但为了类型安全需要转换）
        if ($value === null) {
            return $default;
        }

        // 类型转换：只允许安全转换为字符串的类型
        if (is_string($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        // 其他类型返回默认值
        return $default;
    }

    /**
     * 类型安全地获取可选字符串字段（Entity定义为?string，允许null）
     *
     * @param array<mixed> $data
     */
    private function getNullableString(array $data, string $key): ?string
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        // 显式处理null值
        if ($value === null) {
            return null;
        }

        // 类型转换：只允许安全转换为字符串的类型
        if (is_string($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        // 其他类型返回null
        return null;
    }
}