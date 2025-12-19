<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;
use WechatPayFaceToFaceBundle\Exception\WechatPayException;
use WechatPayFaceToFaceBundle\Repository\FaceToFaceOrderRepository;
use WechatPayFaceToFaceBundle\Response\CreateOrderResponse;
use WechatPayFaceToFaceBundle\Service\FaceToFacePayService;
use WechatPayFaceToFaceBundle\Service\FaceToFaceOrderDataPopulator;
use WechatPayFaceToFaceBundle\Validator\FaceToFaceOrderValidator;

/**
 * 创建订单控制器
 */
final class CreateOrderController extends AbstractController
{
    use FaceToFaceControllerTrait;

    public function __construct(
        private readonly FaceToFacePayService $faceToFacePayService,
        private readonly FaceToFaceOrderRepository $orderRepository,
        private readonly FaceToFaceOrderValidator $orderValidator,
        private readonly FaceToFaceOrderDataPopulator $orderDataPopulator,
    ) {
    }

    #[Route(path: '/api/wechat-pay-face-to-face/create-order', name: 'wechat_pay_face_to_face_create_order', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
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
        if ($user instanceof UserInterface && method_exists($user, 'getId')) {
            $userId = $user->getId();
            if ($userId !== null) {
                $order->setUserId($userId);
            }
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
}
