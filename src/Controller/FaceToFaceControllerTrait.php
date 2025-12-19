<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;
use WechatPayFaceToFaceBundle\Response\QueryOrderResponse;

/**
 * 面对面支付控制器共享方法
 *
 * 提供控制器间共用的响应构建、参数验证等方法
 */
trait FaceToFaceControllerTrait
{
    /**
     * 构造错误响应
     *
     * @param string $error 错误信息
     * @param int $status HTTP状态码
     * @param string|null $errorCode 错误代码（可选）
     */
    protected function buildErrorResponse(string $error, int $status, ?string $errorCode = null): JsonResponse
    {
        $responseData = ['error' => $error];
        if ($errorCode !== null) {
            $responseData['error_code'] = $errorCode;
        }
        return new JsonResponse($responseData, $status);
    }

    /**
     * 解析JSON请求
     *
     * @return array<mixed>|JsonResponse
     */
    protected function parseJsonRequest(Request $request): array|JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->buildErrorResponse('无效的JSON数据', 400);
        }

        return $data;
    }

    /**
     * 验证整数参数并限制范围
     *
     * @param int|null $max 最大值，null表示不限制
     */
    protected function getValidatedIntParam(Request $request, string $param, int $default, int $min, ?int $max = null): int
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
     * 构造查询订单响应
     */
    protected function buildQueryOrderResponse(QueryOrderResponse $response): JsonResponse
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
     * 构造订单列表响应
     *
     * @param array<FaceToFaceOrder> $orders
     */
    protected function buildListOrdersResponse(array $orders): JsonResponse
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
     * 构造获取单个订单响应
     */
    protected function buildGetOrderResponse(FaceToFaceOrder $order): JsonResponse
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
     * 验证订单访问权限
     *
     * @return JsonResponse|null
     */
    protected function validateOrderAccess(FaceToFaceOrder $order): ?JsonResponse
    {
        /** @var UserInterface|null $user */
        $user = $this->getUser();
        if ($user === null) {
            return $this->buildErrorResponse('无权访问此订单', 403);
        }

        if (method_exists($user, 'getId') && $order->getUserId() !== null && $order->getUserId() !== $user->getId()) {
            return $this->buildErrorResponse('无权访问此订单', 403);
        }

        return null;
    }

    /**
     * 获取当前登录用户
     *
     * @return UserInterface|JsonResponse
     */
    protected function getCurrentUser(): UserInterface|JsonResponse
    {
        /** @var UserInterface|null $user */
        $user = $this->getUser();
        if ($user === null) {
            return $this->buildErrorResponse('用户未登录', 401);
        }

        // 检查用户是否有ID方法
        if (method_exists($user, 'getId')) {
            $userId = $user->getId();
            if ($userId === null) {
                return $this->buildErrorResponse('用户ID无效', 400);
            }
        }

        return $user;
    }
}
