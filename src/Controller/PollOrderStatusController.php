<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use WechatPayFaceToFaceBundle\Exception\WechatPayException;
use WechatPayFaceToFaceBundle\Response\QueryOrderResponse;
use WechatPayFaceToFaceBundle\Service\FaceToFacePayService;

/**
 * 轮询订单状态控制器
 */
final class PollOrderStatusController extends AbstractController
{
    use FaceToFaceControllerTrait;

    public function __construct(
        private readonly FaceToFacePayService $faceToFacePayService,
    ) {
    }

    #[Route(path: '/api/wechat-pay-face-to-face/poll-order-status/{outTradeNo}', name: 'wechat_pay_face_to_face_poll_order_status', methods: ['GET'])]
    public function __invoke(Request $request, string $outTradeNo): JsonResponse
    {
        try {
            $pollingParams = $this->extractAndValidatePollingParams($request);

            $response = $this->faceToFacePayService->pollOrderStatus(
                $outTradeNo,
                $pollingParams['max_attempts'],
                $pollingParams['interval_seconds']
            );

            return $this->buildPollOrderStatusResponse($response);
        } catch (WechatPayException $e) {
            return $this->buildErrorResponse($e->getMessage(), 400, $e->getErrorCode());
        } catch (\Exception $e) {
            return $this->buildErrorResponse('系统错误: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 提取并验证轮询参数
     *
     * @return array<string, int>
     */
    private function extractAndValidatePollingParams(Request $request): array
    {
        return [
            'max_attempts' => $this->getValidatedIntParam($request, 'max_attempts', 30, 1, 100),
            'interval_seconds' => $this->getValidatedIntParam($request, 'interval_seconds', 2, 1, 60),
        ];
    }

    /**
     * 构造轮询订单状态响应
     */
    private function buildPollOrderStatusResponse(QueryOrderResponse $response): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => [
                'out_trade_no' => $response->getOutTradeNo(),
                'trade_state' => $response->getTradeState(),
                'trade_state_desc' => $response->getTradeStateDesc(),
                'transaction_id' => $response->getTransactionId(),
                'is_paid' => $response->isPaid(),
                'is_failed' => $response->isFailed(),
                'is_final_state' => $response->isFinalState(),
            ],
        ]);
    }
}
