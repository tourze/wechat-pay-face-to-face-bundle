<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Controller;

use BizUserBundle\Entity\BizUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;
use WechatPayFaceToFaceBundle\Enum\TradeState;
use WechatPayFaceToFaceBundle\Exception\WechatPayException;
use WechatPayFaceToFaceBundle\Repository\FaceToFaceOrderRepository;
use WechatPayFaceToFaceBundle\Response\CreateOrderResponse;
use WechatPayFaceToFaceBundle\Response\QueryOrderResponse;
use WechatPayFaceToFaceBundle\Service\FaceToFacePayService;
use WechatPayFaceToFaceBundle\Service\FaceToFaceOrderDataPopulator;
use WechatPayFaceToFaceBundle\Validator\FaceToFaceOrderValidator;

#[Route(path: '/api/wechat-pay-face-to-face', name: 'wechat_pay_face_to_face_root')]
final class FaceToFacePayController extends AbstractController
{
    public function __construct(
        private readonly FaceToFacePayService $faceToFacePayService,
        private readonly FaceToFaceOrderRepository $orderRepository,
        private readonly FaceToFaceOrderValidator $orderValidator,
        private readonly FaceToFaceOrderDataPopulator $orderDataPopulator,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'WeChat Pay Face-to-Face API is working',
            'endpoints' => [
                'create_order' => '/api/wechat-pay-face-to-face/create-order',
                'query_order' => '/api/wechat-pay-face-to-face/query-order/{outTradeNo}',
                'close_order' => '/api/wechat-pay-face-to-face/close-order/{outTradeNo}',
                'poll_order_status' => '/api/wechat-pay-face-to-face/poll-order-status/{outTradeNo}',
                'list_orders' => '/api/wechat-pay-face-to-face/orders',
                'get_order' => '/api/wechat-pay-face-to-face/order/{outTradeNo}',
            ]
        ]);
    }

    #[Route(path: '/create-order', name: 'wechat_pay_face_to_face_create_order', methods: ['POST'])]
    public function createOrder(Request $request): JsonResponse
    {
        try {
            $data = $this->parseJsonRequest($request);
            if ($data instanceof JsonResponse) {
                return $data;
            }

            $order = $this->createAndValidateOrder($data);
            $this->attachUserToOrder($order);

            $this->orderRepository->save($order);
            $response = $this->faceToFacePayService->createOrder($order);

            return $this->buildSuccessJsonResponse($order, $response);
        } catch (WechatPayException $e) {
            return $this->buildErrorResponse($e->getMessage(), 400, $e->getErrorCode());
        } catch (\Exception $e) {
            return $this->buildErrorResponse('系统错误: ' . $e->getMessage(), 500);
        }
    }

    #[Route(path: '/query-order/{outTradeNo}', name: 'wechat_pay_face_to_face_query_order', methods: ['GET'])]
    public function queryOrder(string $outTradeNo): JsonResponse
    {
        try {
            $response = $this->faceToFacePayService->queryOrder($outTradeNo);

            return $this->buildQueryOrderResponse($response);
        } catch (WechatPayException $e) {
            return $this->buildErrorResponse($e->getMessage(), 400, $e->getErrorCode());
        } catch (\Exception $e) {
            return $this->buildErrorResponse('系统错误: ' . $e->getMessage(), 500);
        }
    }

    #[Route(path: '/close-order/{outTradeNo}', name: 'wechat_pay_face_to_face_close_order', methods: ['POST'])]
    public function closeOrder(string $outTradeNo): JsonResponse
    {
        try {
            $this->faceToFacePayService->closeOrder($outTradeNo);

            return $this->buildCloseOrderResponse();
        } catch (WechatPayException $e) {
            return $this->buildErrorResponse($e->getMessage(), 400, $e->getErrorCode());
        } catch (\Exception $e) {
            return $this->buildErrorResponse('系统错误: ' . $e->getMessage(), 500);
        }
    }

    #[Route(path: '/poll-order-status/{outTradeNo}', name: 'wechat_pay_face_to_face_poll_order_status', methods: ['GET'])]
    public function pollOrderStatus(
        Request $request,
        string $outTradeNo
    ): JsonResponse {
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

    #[Route(path: '/orders', name: 'wechat_pay_face_to_face_list_orders', methods: ['GET'])]
    public function listOrders(Request $request): JsonResponse
    {
        try {
            $paginationParams = $this->extractAndValidatePaginationParams($request);

            $user = $this->getCurrentUser();
            if ($user instanceof JsonResponse) {
                return $user;
            }

            $orders = $this->orderRepository->findByUserId(
                $user->getId(),
                $paginationParams['limit'],
                $paginationParams['offset']
            );

            return $this->buildListOrdersResponse($orders);
        } catch (\Exception $e) {
            return $this->buildErrorResponse('系统错误: ' . $e->getMessage(), 500);
        }
    }

    #[Route(path: '/order/{outTradeNo}', name: 'wechat_pay_face_to_face_get_order', methods: ['GET'])]
    public function getOrder(string $outTradeNo): JsonResponse
    {
        try {
            $order = $this->orderRepository->findByOutTradeNo($outTradeNo);
            if ($order === null) {
                return $this->buildErrorResponse('订单不存在', 404);
            }

            $accessCheck = $this->validateOrderAccess($order);
            if ($accessCheck instanceof JsonResponse) {
                return $accessCheck;
            }

            return $this->buildGetOrderResponse($order);
        } catch (\Exception $e) {
            return $this->buildErrorResponse('系统错误: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 解析JSON请求
     *
     * @return array<mixed>|JsonResponse
     */
    private function parseJsonRequest(Request $request): array|JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->buildErrorResponse('无效的JSON数据', 400);
        }

        return $data;
    }

    /**
     * 创建并验证订单
     *
     * @param array<mixed> $data
     * @throws WechatPayException
     */
    private function createAndValidateOrder(array $data): FaceToFaceOrder
    {
        $validationResult = $this->orderValidator->validateCreateOrderData($data);
        if ($validationResult !== null) {
            throw new WechatPayException('参数验证失败');
        }

        return $this->orderDataPopulator->populateOrderFromData($data);
    }

    /**
     * 附加用户信息到订单
     */
    private function attachUserToOrder(FaceToFaceOrder $order): void
    {
        $user = $this->getUser();
        if ($user instanceof BizUser) {
            $order->setUserId($user->getId());
        }
    }

    
    
    
    
    /**
     * 构造成功响应
     */
    private function buildSuccessJsonResponse(FaceToFaceOrder $order, CreateOrderResponse $response): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => [
                'out_trade_no' => $order->getOutTradeNo(),
                'code_url' => $response->getCodeUrl(),
                'prepay_id' => $response->getPrepayId(),
                'total_fee' => $order->getTotalFee(),
                'currency' => $order->getCurrency(),
                'body' => $order->getBody(),
                'expire_time' => $order->getExpireTime(),
            ],
        ]);
    }

    /**
     * 构造查询订单响应
     */
    private function buildQueryOrderResponse(QueryOrderResponse $response): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => [
                'out_trade_no' => $response->getOutTradeNo(),
                'trade_state' => $response->getTradeState(),
                'trade_state_desc' => $response->getTradeStateDesc(),
                'transaction_id' => $response->getTransactionId(),
                'bank_type' => $response->getBankType(),
                'success_time' => $response->getSuccessTime(),
                'pay_type' => $response->getPayType(),
                'time_end' => $response->getTimeEnd(),
                'is_paid' => $response->isPaid(),
                'is_failed' => $response->isFailed(),
                'is_not_paid' => $response->isNotPaid(),
                'is_final_state' => $response->isFinalState(),
            ],
        ]);
    }

    /**
     * 构造关闭订单响应
     */
    private function buildCloseOrderResponse(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => '订单关闭成功',
        ]);
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

    /**
     * 提取并验证分页参数
     *
     * @return array<string, int>
     */
    private function extractAndValidatePaginationParams(Request $request): array
    {
        return [
            'limit' => $this->getValidatedIntParam($request, 'limit', 20, 1, 100),
            'offset' => $this->getValidatedIntParam($request, 'offset', 0, 0, null),
        ];
    }

    /**
     * 获取当前登录用户
     *
     * @return BizUser|JsonResponse
     */
    private function getCurrentUser(): BizUser|JsonResponse
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->buildErrorResponse('用户未登录', 401);
        }

        assert($user instanceof BizUser);
        $userId = $user->getId();
        if ($userId === null) {
            return $this->buildErrorResponse('用户ID无效', 400);
        }

        return $user;
    }

    /**
     * 构造订单列表响应
     *
     * @param array<FaceToFaceOrder> $orders
     */
    private function buildListOrdersResponse(array $orders): JsonResponse
    {
        $orderData = array_map(function (FaceToFaceOrder $order) {
            return [
                'id' => $order->getId(),
                'out_trade_no' => $order->getOutTradeNo(),
                'total_fee' => $order->getTotalFee(),
                'currency' => $order->getCurrency(),
                'body' => $order->getBody(),
                'trade_state' => $order->getTradeState(),
                'trade_state_desc' => $order->getTradeStateDesc(),
                'transaction_id' => $order->getTransactionId(),
                'created_at' => $order->getCreatedAt(),
                'updated_at' => $order->getUpdatedAt(),
                'expire_time' => $order->getExpireTime(),
            ];
        }, $orders);

        return new JsonResponse([
            'success' => true,
            'data' => $orderData,
        ]);
    }

    /**
     * 验证订单访问权限
     *
     * @return JsonResponse|null
     */
    private function validateOrderAccess(FaceToFaceOrder $order): ?JsonResponse
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->buildErrorResponse('无权访问此订单', 403);
        }

        if ($user instanceof BizUser && $order->getUserId() !== null && $order->getUserId() !== $user->getId()) {
            return $this->buildErrorResponse('无权访问此订单', 403);
        }

        return null;
    }

    /**
     * 构造获取单个订单响应
     */
    private function buildGetOrderResponse(FaceToFaceOrder $order): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $order->getId(),
                'out_trade_no' => $order->getOutTradeNo(),
                'total_fee' => $order->getTotalFee(),
                'currency' => $order->getCurrency(),
                'body' => $order->getBody(),
                'trade_state' => $order->getTradeState(),
                'trade_state_desc' => $order->getTradeStateDesc(),
                'transaction_id' => $order->getTransactionId(),
                'code_url' => $order->getCodeUrl(),
                'prepay_id' => $order->getPrepayId(),
                'created_at' => $order->getCreatedAt(),
                'updated_at' => $order->getUpdatedAt(),
                'expire_time' => $order->getExpireTime(),
                'success_time' => $order->getSuccessTime(),
                'pay_type' => $order->getPayType(),
                'bank_type' => $order->getBankType(),
            ],
        ]);
    }

    /**
     * 验证整数参数并限制范围
     *
     * @param int|null $max 最大值，null表示不限制
     */
    private function getValidatedIntParam(Request $request, string $param, int $default, int $min, ?int $max = null): int
    {
        $value = $request->query->get($param, $default);
        $intValue = is_numeric($value) ? (int) $value : $default;
        $intValue = max($min, $intValue);

        if ($max !== null) {
            $intValue = min($max, $intValue);
        }

        return $intValue;
    }

    /**
     * 构造错误响应
     *
     * @param string $error 错误信息
     * @param int $status HTTP状态码
     * @param string|null $errorCode 错误代码（可选）
     */
    private function buildErrorResponse(string $error, int $status, ?string $errorCode = null): JsonResponse
    {
        $responseData = ['error' => $error];
        if ($errorCode !== null) {
            $responseData['error_code'] = $errorCode;
        }
        return new JsonResponse($responseData, $status);
    }
}
