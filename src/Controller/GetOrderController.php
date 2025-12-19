<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use WechatPayFaceToFaceBundle\Repository\FaceToFaceOrderRepository;

/**
 * 获取单个订单控制器
 */
final class GetOrderController extends AbstractController
{
    use FaceToFaceControllerTrait;

    public function __construct(
        private readonly FaceToFaceOrderRepository $orderRepository,
    ) {
    }

    #[Route(path: '/api/wechat-pay-face-to-face/order/{outTradeNo}', name: 'wechat_pay_face_to_face_get_order', methods: ['GET'])]
    public function __invoke(string $outTradeNo): JsonResponse
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
}
