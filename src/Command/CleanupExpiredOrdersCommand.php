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
use WechatPayFaceToFaceBundle\Service\FaceToFacePayService;

#[AsCronTask(expression: '*/10 * * * *')]
#[AsCommand(
    name: 'wechat-pay:face-to-face:cleanup-expired',
    description: '清理过期的面对面收款订单'
)]
class CleanupExpiredOrdersCommand extends Command
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
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, '预演模式，不实际关闭订单')
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, '批处理大小', 100)
            ->setHelp('此命令用于清理过期的面对面收款订单');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $batchSize = $this->getValidatedBatchSize($input, $io);

        $this->displayCommandHeader($io, $dryRun);

        $expiredOrders = $this->orderRepository->findExpiredOrdersToClose();
        if (count($expiredOrders) === 0) {
            $io->success('没有找到需要清理的过期订单');
            return Command::SUCCESS;
        }

        $io->info(sprintf('找到 %d 个过期订单', count($expiredOrders)));

        $result = $this->processOrders($expiredOrders, $batchSize, $dryRun, $io);
        $this->displayResultTable($io, $result);
        $this->displayCompletionMessage($io, $dryRun, $result['successCount']);

        return Command::SUCCESS;
    }

    private function getValidatedBatchSize(InputInterface $input, SymfonyStyle $io): int
    {
        $batchSizeOption = $input->getOption('batch-size');

        if ($batchSizeOption === null) {
            return 100;
        }

        if (!is_numeric($batchSizeOption)) {
            $io->warning('批处理大小参数无效，使用默认值 100');
            return 100;
        }

        return (int) $batchSizeOption;
    }

    private function displayCommandHeader(SymfonyStyle $io, bool $dryRun): void
    {
        $io->title('清理过期面对面收款订单');

        if ($dryRun) {
            $io->warning('预演模式：不会实际关闭订单');
        }
    }

    /**
     * @param FaceToFaceOrder[] $expiredOrders
     * @return array{successCount: int, failCount: int}
     */
    private function processOrders(array $expiredOrders, int $batchSize, bool $dryRun, SymfonyStyle $io): array
    {
        $successCount = 0;
        $failCount = 0;

        foreach ($expiredOrders as $order) {
            if ($this->processSingleOrder($order, $dryRun, $io)) {
                $successCount++;
                $this->checkBatchProgress($successCount, $batchSize, $io);
            } else {
                $failCount++;
            }
        }

        return [
            'successCount' => $successCount,
            'failCount' => $failCount,
        ];
    }

    private function processSingleOrder(FaceToFaceOrder $order, bool $dryRun, SymfonyStyle $io): bool
    {
        try {
            if (!$dryRun) {
                $this->faceToFacePayService->closeOrder($order->getOutTradeNo());
            }

            $io->text(sprintf(
                '[%s] 订单 %s 已关闭',
                $dryRun ? '预演' : '成功',
                $order->getOutTradeNo()
            ));

            return true;
        } catch (\Exception $e) {
            $io->error(sprintf(
                '关闭订单 %s 失败: %s',
                $order->getOutTradeNo(),
                $e->getMessage()
            ));

            return false;
        }
    }

    private function checkBatchProgress(int $successCount, int $batchSize, SymfonyStyle $io): void
    {
        if ($successCount % $batchSize === 0) {
            $io->text(sprintf('已处理 %d 个订单', $successCount));
            // 可以在这里添加延迟以避免API限制
            // sleep(1);
        }
    }

    /**
     * @param array{successCount: int, failCount: int} $result
     */
    private function displayResultTable(SymfonyStyle $io, array $result): void
    {
        $io->newLine();
        $io->table(
            ['结果', '数量'],
            [
                ['成功关闭', $result['successCount']],
                ['关闭失败', $result['failCount']],
                ['总计', $result['successCount'] + $result['failCount']],
            ]
        );
    }

    private function displayCompletionMessage(SymfonyStyle $io, bool $dryRun, int $successCount): void
    {
        if ($dryRun) {
            $io->success('预演完成');
        } else {
            $io->success(sprintf('清理完成，成功关闭 %d 个订单', $successCount));
        }
    }
}
