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
use WechatPayFaceToFaceBundle\Command\SyncOrderStatusCommand;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;
use WechatPayFaceToFaceBundle\Repository\FaceToFaceOrderRepository;
use WechatPayFaceToFaceBundle\Response\QueryOrderResponse;
use WechatPayFaceToFaceBundle\Service\FaceToFacePayService;

#[CoversClass(SyncOrderStatusCommand::class)]
#[RunTestsInSeparateProcesses]
final class SyncOrderStatusCommandTest extends AbstractCommandTestCase
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
            ->method('findUnpaidAndNotExpiredOrders')
            ->with(30 * 60)
            ->willReturn([]);

        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionMinutes(): void
    {
        $order = $this->createOrder('ORDER789');

        $this->orderRepository
            ->expects($this->once())
            ->method('findUnpaidAndNotExpiredOrders')
            ->with(60 * 60)
            ->willReturn([$order]);

        $response = new QueryOrderResponse([
            'trade_state' => 'SUCCESS',
            'out_trade_no' => 'ORDER789',
        ]);

        $this->faceToFacePayService
            ->expects($this->once())
            ->method('queryOrder')
            ->with('ORDER789')
            ->willReturn($response);

        $exitCode = $this->commandTester->execute([
            '--minutes' => 60,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('同步最近 60 分钟内的订单', $output);
        $this->assertStringContainsString('[成功] 订单 ORDER789 已支付', $output);
    }

    public function testOptionBatchSize(): void
    {
        $order = $this->createOrder('ORDER987');

        $this->orderRepository
            ->expects($this->once())
            ->method('findUnpaidAndNotExpiredOrders')
            ->with(30 * 60)
            ->willReturn([$order]);

        $response = new QueryOrderResponse([
            'trade_state' => 'NOTPAY',
            'out_trade_no' => 'ORDER987',
        ]);

        $this->faceToFacePayService
            ->expects($this->once())
            ->method('queryOrder')
            ->with('ORDER987')
            ->willReturn($response);

        $exitCode = $this->commandTester->execute([
            '--batch-size' => 2,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('[未支付] 订单 ORDER987 仍处于未支付状态', $output);
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

        /** @var SyncOrderStatusCommand $command */
        $command = self::getService(SyncOrderStatusCommand::class);

        $application = new Application();
        $application->addCommand($command);

        $this->commandTester = new CommandTester($application->find('wechat-pay:face-to-face:sync-order-status'));
    }

    private function createOrder(string $outTradeNo): FaceToFaceOrder
    {
        $order = new FaceToFaceOrder();
        $order->setOutTradeNo($outTradeNo);
        $order->setAppid('wx-test-appid');
        $order->setMchid('test-mchid');
        $order->setTotalFee(200);
        $order->setBody('测试商品');

        return $order;
    }
}
