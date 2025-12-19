<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * 面对面支付API信息控制器
 *
 * 返回API端点信息
 */
final class FaceToFaceApiInfoController extends AbstractController
{
    #[Route(path: '/api/wechat-pay-face-to-face', name: 'wechat_pay_face_to_face_root', methods: ['GET'])]
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
}
