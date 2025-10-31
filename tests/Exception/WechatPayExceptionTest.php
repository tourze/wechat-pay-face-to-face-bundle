<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use WechatPayFaceToFaceBundle\Exception\WechatPayException;

#[CoversClass(WechatPayException::class)]
final class WechatPayExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionWithDefaultValues(): void
    {
        $exception = new WechatPayException();

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getErrorCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithMessage(): void
    {
        $exception = new WechatPayException('Test error message');

        $this->assertSame('Test error message', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getErrorCode());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $exception = new WechatPayException('Test error message', 400);

        $this->assertSame('Test error message', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertNull($exception->getErrorCode());
    }

    public function testExceptionWithErrorCode(): void
    {
        $exception = new WechatPayException('Test error message', 400, null, 'PAY_ERROR');

        $this->assertSame('Test error message', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertSame('PAY_ERROR', $exception->getErrorCode());
    }

    public function testExceptionWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new WechatPayException('Test error message', 400, $previous, 'PAY_ERROR');

        $this->assertSame('Test error message', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertSame('PAY_ERROR', $exception->getErrorCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testSetErrorCode(): void
    {
        $exception = new WechatPayException();
        $exception->setErrorCode('NEW_ERROR_CODE');

        $this->assertSame('NEW_ERROR_CODE', $exception->getErrorCode());
    }

    public function testSetErrorCodeToNull(): void
    {
        $exception = new WechatPayException('Test', 0, null, 'OLD_ERROR');
        $exception->setErrorCode(null);

        $this->assertNull($exception->getErrorCode());
    }

    public function testExceptionIsInstanceOfException(): void
    {
        $exception = new WechatPayException();

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionWithAllParameters(): void
    {
        $previous = new \InvalidArgumentException('Invalid argument');
        $exception = new WechatPayException(
            'Payment failed',
            500,
            $previous,
            'PAYMENT_FAILED'
        );

        $this->assertSame('Payment failed', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame('PAYMENT_FAILED', $exception->getErrorCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception->getPrevious());
    }
}