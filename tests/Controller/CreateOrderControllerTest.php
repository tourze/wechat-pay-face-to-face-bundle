<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use WechatPayFaceToFaceBundle\Controller\CreateOrderController;

#[CoversClass(CreateOrderController::class)]
#[RunTestsInSeparateProcesses]
final class CreateOrderControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return CreateOrderController::class;
    }
}
