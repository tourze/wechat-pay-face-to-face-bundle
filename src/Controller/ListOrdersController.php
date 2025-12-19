<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use WechatPayFaceToFaceBundle\Repository\FaceToFaceOrderRepository;

/**
 * 订单列表控制器
 */
final class ListOrdersController extends AbstractController
{
    use FaceToFaceControllerTrait;

    public function __construct(
        private readonly FaceToFaceOrderRepository $orderRepository,
    ) {
    }

    #[Route(path: '/api/wechat-pay-face-to-face/orders', name: 'wechat_pay_face_to_face_list_orders', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $paginationParams = $this->extractAndValidatePaginationParams($request);

            $user = $this->getCurrentUser();
            if ($user instanceof JsonResponse) {
                return $user;
            }

            // 获取用户ID，确保类型安全
            $userId = null;
            if (method_exists($user, 'getId')) {
                $userId = $user->getId();
            }

            if ($userId === null) {
                return $this->buildErrorResponse('用户ID无效', 400);
            }

            $orders = $this->orderRepository->findByUserId(
                $userId,
                $paginationParams['limit'],
                $paginationParams['offset']
            );

            return $this->buildListOrdersResponse($orders);
        } catch (\Exception $e) {
            return $this->buildErrorResponse('系统错误: ' . $e->getMessage(), 500);
        }
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
}
