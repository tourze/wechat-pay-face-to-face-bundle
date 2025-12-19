<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use WechatPayFaceToFaceBundle\Controller\FaceToFaceApiInfoController;

#[CoversClass(FaceToFaceApiInfoController::class)]
#[RunTestsInSeparateProcesses]
final class FaceToFaceApiInfoControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return FaceToFaceApiInfoController::class;
    }
}
