<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Exception;

use Exception;

class WechatPayException extends Exception
{
    private ?string $errorCode = null;

    public function __construct(string $message = '', int|string $code = 0, ?\Throwable $previous = null, ?string $errorCode = null)
    {
        parent::__construct($message, (int)$code, $previous);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function setErrorCode(?string $errorCode): void
    {
        $this->errorCode = $errorCode;
    }
}
