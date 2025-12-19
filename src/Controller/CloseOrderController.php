<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use WechatPayFaceToFaceBundle\Exception\WechatPayException;
use WechatPayFaceToFaceBundle\Service\FaceToFacePayService;

/**
 * 关闭订单控制器
 */
final class CloseOrderController extends AbstractController
{
    use FaceToFaceControllerTrait;

    public function __construct(
        private readonly FaceToFacePayService $faceToFacePayService,
    ) {
    }

    #[Route(path: '/api/wechat-pay-face-to-face/close-order/{outTradeNo}', name: 'wechat_pay_face_to_face_close_order', methods: ['POST'])]
    public function __invoke(string $outTradeNo): JsonResponse
    {
        try {
            $this->faceToFacePayService->closeOrder($outTradeNo);

            return new JsonResponse([
                'success' => true,
                'message' => '订单关闭成功',
            ]);
        } catch (WechatPayException $e) {
            return $this->buildErrorResponse($e->getMessage(), 400, $e->getErrorCode());
        } catch (\Exception $e) {
            return $this->buildErrorResponse('系统错误: ' . $e->getMessage(), 500);
        }
    }
}
