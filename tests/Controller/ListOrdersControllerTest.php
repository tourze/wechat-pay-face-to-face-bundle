<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use WechatPayFaceToFaceBundle\Controller\ListOrdersController;

#[CoversClass(ListOrdersController::class)]
#[RunTestsInSeparateProcesses]
final class ListOrdersControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return ListOrdersController::class;
    }
}
