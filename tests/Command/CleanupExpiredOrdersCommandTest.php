<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use WechatPayFaceToFaceBundle\Command\CleanupExpiredOrdersCommand;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;
use WechatPayFaceToFaceBundle\Repository\FaceToFaceOrderRepository;
use WechatPayFaceToFaceBundle\Service\FaceToFacePayService;

#[CoversClass(CleanupExpiredOrdersCommand::class)]
#[RunTestsInSeparateProcesses]
final class CleanupExpiredOrdersCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    /** @var MockObject&FaceToFaceOrderRepository */
    private FaceToFaceOrderRepository $orderRepository;

    /** @var MockObject&FaceToFacePayService */
    private FaceToFacePayService $faceToFacePayService;

    public function testCommandExecute(): void
    {
        $this->orderRepository
            ->expects($this->once())
            ->method('findExpiredOrdersToClose')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionDryRun(): void
    {
        $order = $this->createOrder('ORDER123');

        $this->orderRepository
            ->expects($this->once())
            ->method('findExpiredOrdersToClose')
            ->willReturn([$order]);

        $this->faceToFacePayService
            ->expects($this->never())
            ->method('closeOrder');

        $exitCode = $this->commandTester->execute([
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('预演模式：不会实际关闭订单', $output);
        $this->assertStringContainsString('[预演] 订单 ORDER123 已关闭', $output);
    }

    public function testOptionBatchSize(): void
    {
        $order = $this->createOrder('ORDER456');

        $this->orderRepository
            ->expects($this->once())
            ->method('findExpiredOrdersToClose')
            ->willReturn([$order]);

        $this->faceToFacePayService
            ->expects($this->once())
            ->method('closeOrder')
            ->with('ORDER456')
            ->willReturn(true);

        $exitCode = $this->commandTester->execute([
            '--batch-size' => 1,
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $container = self::getContainer();

        $this->orderRepository = $this->createMock(FaceToFaceOrderRepository::class);
        $this->faceToFacePayService = $this->createMock(FaceToFacePayService::class);

        $container->set(FaceToFaceOrderRepository::class, $this->orderRepository);
        $container->set(FaceToFacePayService::class, $this->faceToFacePayService);

        self::clearServiceLocatorCache();

        /** @var CleanupExpiredOrdersCommand $command */
        $command = self::getService(CleanupExpiredOrdersCommand::class);

        $application = new Application();
        $application->addCommand($command);

        $this->commandTester = new CommandTester($application->find('wechat-pay:face-to-face:cleanup-expired'));
    }

    private function createOrder(string $outTradeNo): FaceToFaceOrder
    {
        $order = new FaceToFaceOrder();
        $order->setOutTradeNo($outTradeNo);
        $order->setAppid('wx-test-appid');
        $order->setMchid('test-mchid');
        $order->setTotalFee(100);
        $order->setBody('测试商品');

        return $order;
    }
}
