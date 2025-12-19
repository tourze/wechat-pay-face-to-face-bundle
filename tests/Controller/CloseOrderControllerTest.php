<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use WechatPayFaceToFaceBundle\Controller\CloseOrderController;

#[CoversClass(CloseOrderController::class)]
#[RunTestsInSeparateProcesses]
final class CloseOrderControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return CloseOrderController::class;
    }
}
