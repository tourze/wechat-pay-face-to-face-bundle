<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use WechatPayFaceToFaceBundle\WechatPayFaceToFaceBundle;

#[CoversClass(WechatPayFaceToFaceBundle::class)]
#[RunTestsInSeparateProcesses]
final class WechatPayFaceToFaceBundleTest extends AbstractBundleTestCase
{
}
