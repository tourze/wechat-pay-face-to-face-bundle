<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\Symfony\CronJob\Attribute\AsCronTask;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;
use WechatPayFaceToFaceBundle\Repository\FaceToFaceOrderRepository;
use WechatPayFaceToFaceBundle\Response\QueryOrderResponse;
use WechatPayFaceToFaceBundle\Service\FaceToFacePayService;

#[AsCronTask(expression: '*/3 * * * *')]
#[AsCommand(
    name: 'wechat-pay:face-to-face:sync-order-status',
    description: '同步面对面收款订单状态'
)]
class SyncOrderStatusCommand extends Command
{
    public function __construct(
        private readonly FaceToFaceOrderRepository $orderRepository,
        private readonly FaceToFacePayService $faceToFacePayService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('minutes', 'm', InputOption::VALUE_OPTIONAL, '同步最近多少分钟内的订单', 30)
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, '批处理大小', 50)
            ->setHelp('此命令用于同步面对面收款订单的状态');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $minutes = $this->getValidatedMinutes($input, $io);
        $batchSize = $this->getValidatedBatchSize($input, $io);

        $this->displayCommandHeader($io, $minutes);

        $unpaidOrders = $this->orderRepository->findUnpaidAndNotExpiredOrders($minutes * 60);
        if (count($unpaidOrders) === 0) {
            $io->success('没有找到需要同步状态的订单');
            return Command::SUCCESS;
        }

        $io->info(sprintf('找到 %d 个未支付订单', count($unpaidOrders)));

        $result = $this->processOrderStatusSync($unpaidOrders, $batchSize, $io);
        $this->displayResultTable($io, $result);
        $this->displayCompletionMessage($io, $result['successCount'], $result['paidCount']);

        return Command::SUCCESS;
    }

    private function getValidatedMinutes(InputInterface $input, SymfonyStyle $io): int
    {
        $minutesOption = $input->getOption('minutes');

        if ($minutesOption === null) {
            return 30;
        }

        if (!is_numeric($minutesOption)) {
            $io->warning('同步时间间隔参数无效，使用默认值 30 分钟');
            return 30;
        }

        $minutes = (int) $minutesOption;
        if ($minutes <= 0) {
            $io->warning('同步时间间隔必须大于0，使用默认值 30 分钟');
            return 30;
        }

        return $minutes;
    }

    private function getValidatedBatchSize(InputInterface $input, SymfonyStyle $io): int
    {
        $batchSizeOption = $input->getOption('batch-size');

        if ($batchSizeOption === null) {
            return 50;
        }

        if (!is_numeric($batchSizeOption)) {
            $io->warning('批处理大小参数无效，使用默认值 50');
            return 50;
        }

        $batchSize = (int) $batchSizeOption;
        if ($batchSize <= 0) {
            $io->warning('批处理大小必须大于0，使用默认值 50');
            return 50;
        }

        return $batchSize;
    }

    private function displayCommandHeader(SymfonyStyle $io, int $minutes): void
    {
        $io->title('同步面对面收款订单状态');
        $io->info(sprintf('同步最近 %d 分钟内的订单', $minutes));
    }

    /**
     * @param FaceToFaceOrder[] $unpaidOrders
     * @return array{successCount: int, failCount: int, paidCount: int}
     */
    private function processOrderStatusSync(array $unpaidOrders, int $batchSize, SymfonyStyle $io): array
    {
        $successCount = 0;
        $failCount = 0;
        $paidCount = 0;

        foreach ($unpaidOrders as $order) {
            $result = $this->processSingleOrderSync($order, $io);

            if ($result['success']) {
                $successCount++;
                if ($result['paid']) {
                    $paidCount++;
                }
                $this->checkBatchProgress($successCount, $batchSize, $io);
            } else {
                $failCount++;
            }
        }

        return [
            'successCount' => $successCount,
            'failCount' => $failCount,
            'paidCount' => $paidCount,
        ];
    }

    /**
     * @return array{success: bool, paid: bool}
     */
    private function processSingleOrderSync(FaceToFaceOrder $order, SymfonyStyle $io): array
    {
        try {
            $response = $this->faceToFacePayService->queryOrder($order->getOutTradeNo());

            $this->displayOrderStatus($order, $response, $io);

            return [
                'success' => true,
                'paid' => $response->isPaid(),
            ];
        } catch (\Exception $e) {
            $io->error(sprintf(
                '查询订单 %s 状态失败: %s',
                $order->getOutTradeNo(),
                $e->getMessage()
            ));

            return [
                'success' => false,
                'paid' => false,
            ];
        }
    }

    private function displayOrderStatus(FaceToFaceOrder $order, QueryOrderResponse $response, SymfonyStyle $io): void
    {
        if ($response->isPaid()) {
            $io->text(sprintf(
                '[成功] 订单 %s 已支付，金额: %d 分',
                $order->getOutTradeNo(),
                $order->getTotalFee()
            ));
        } elseif ($response->isFailed()) {
            $io->text(sprintf(
                '[失败] 订单 %s 支付失败，状态: %s',
                $order->getOutTradeNo(),
                $response->getTradeState()
            ));
        } else {
            $io->text(sprintf(
                '[未支付] 订单 %s 仍处于未支付状态',
                $order->getOutTradeNo()
            ));
        }
    }

    private function checkBatchProgress(int $successCount, int $batchSize, SymfonyStyle $io): void
    {
        if ($successCount % $batchSize === 0) {
            $io->text(sprintf('已处理 %d 个订单', $successCount));
            // 添加延迟以避免API限制
            sleep(1);
        }
    }

    /**
     * @param array{successCount: int, failCount: int, paidCount: int} $result
     */
    private function displayResultTable(SymfonyStyle $io, array $result): void
    {
        $io->newLine();
        $io->table(
            ['结果', '数量'],
            [
                ['查询成功', $result['successCount']],
                ['其中已支付', $result['paidCount']],
                ['查询失败', $result['failCount']],
                ['总计', $result['successCount'] + $result['failCount']],
            ]
        );
    }

    private function displayCompletionMessage(SymfonyStyle $io, int $successCount, int $paidCount): void
    {
        $io->success(sprintf('同步完成，处理了 %d 个订单，其中 %d 个已支付', $successCount, $paidCount));
    }
}
