<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use WechatPayFaceToFaceBundle\Controller\QueryOrderController;

#[CoversClass(QueryOrderController::class)]
#[RunTestsInSeparateProcesses]
final class QueryOrderControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return QueryOrderController::class;
    }
}
