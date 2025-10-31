<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Response;

class CreateOrderResponse
{
    private string $codeUrl;
    private string $prepayId;
    private ?string $errMsg = null;
    private ?string $errCode = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->codeUrl = isset($data['code_url']) && is_string($data['code_url']) ? $data['code_url'] : '';
        $this->prepayId = isset($data['prepay_id']) && is_string($data['prepay_id']) ? $data['prepay_id'] : '';
        $this->errMsg = isset($data['errmsg']) && is_string($data['errmsg']) ? $data['errmsg'] : null;
        $this->errCode = isset($data['errcode']) ? (is_string($data['errcode']) || is_numeric($data['errcode']) ? (string)$data['errcode'] : null) : null;
    }

    public function getCodeUrl(): string
    {
        return $this->codeUrl;
    }

    public function getPrepayId(): string
    {
        return $this->prepayId;
    }

    public function getErrMsg(): ?string
    {
        return $this->errMsg;
    }

    public function getErrCode(): ?string
    {
        return $this->errCode;
    }

    public function isSuccess(): bool
    {
        return $this->codeUrl !== '' && $this->prepayId !== '';
    }
}
