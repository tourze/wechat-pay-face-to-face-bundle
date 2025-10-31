<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Response;

class QueryOrderResponse
{
    private ?string $tradeState;
    private ?string $tradeStateDesc = null;
    private ?string $transactionId = null;
    private ?string $outTradeNo = null;
    private ?string $bankType = null;
    private ?string $successTime = null;
    private ?string $payType = null;
    private ?int $timeEnd = null;
    private ?string $errMsg = null;
    private ?string $errCode = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->tradeState = $this->getStringValue($data, 'trade_state', '');
        $this->tradeStateDesc = $this->getStringValue($data, 'trade_state_desc');
        $this->transactionId = $this->getStringValue($data, 'transaction_id');
        $this->outTradeNo = $this->getStringValue($data, 'out_trade_no');
        $this->bankType = $this->getStringValue($data, 'bank_type');
        $this->successTime = $this->getStringValue($data, 'success_time');
        $this->payType = $this->getStringValue($data, 'pay_type');
        $this->timeEnd = $this->getTimeEndValue($data);
        $this->errMsg = $this->getStringValue($data, 'errmsg');
        $this->errCode = $this->getStringValue($data, 'errcode');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getStringValue(array $data, string $key, ?string $default = null): ?string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getTimeEndValue(array $data): ?int
    {
        if (!isset($data['time_end'])) {
            return null;
        }

        return is_numeric($data['time_end']) ? (int) $data['time_end'] : null;
      }

    public function getTradeState(): string
    {
        return $this->tradeState ?? '';
    }

    public function getTradeStateDesc(): ?string
    {
        return $this->tradeStateDesc;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function getOutTradeNo(): ?string
    {
        return $this->outTradeNo;
    }

    public function getBankType(): ?string
    {
        return $this->bankType;
    }

    public function getSuccessTime(): ?string
    {
        return $this->successTime;
    }

    public function getPayType(): ?string
    {
        return $this->payType;
    }

    public function getTimeEnd(): ?int
    {
        return $this->timeEnd;
    }

    public function getErrMsg(): ?string
    {
        return $this->errMsg;
    }

    public function getErrCode(): ?string
    {
        return $this->errCode;
    }

    /**
     * 检查是否支付成功
     */
    public function isPaid(): bool
    {
        return $this->tradeState === 'SUCCESS';
    }

    /**
     * 检查是否支付失败
     */
    public function isFailed(): bool
    {
        return in_array($this->tradeState, ['CLOSED', 'PAYERROR', 'NOTPAYNOT'], true);
    }

    /**
     * 检查是否未支付
     */
    public function isNotPaid(): bool
    {
        return $this->tradeState === 'NOTPAY';
    }

    /**
     * 检查是否为最终状态
     */
    public function isFinalState(): bool
    {
        return $this->isPaid() || $this->isFailed() || $this->tradeState === 'REFUND';
    }
}
