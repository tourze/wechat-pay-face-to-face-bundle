<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
// use Tourze\JsonRPCLockBundle\Service\LockService;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;
use WechatPayFaceToFaceBundle\Enum\TradeState;
use WechatPayFaceToFaceBundle\Exception\WechatPayException;
use WechatPayFaceToFaceBundle\Repository\FaceToFaceOrderRepository;
use WechatPayFaceToFaceBundle\Response\CreateOrderResponse;
use WechatPayFaceToFaceBundle\Response\QueryOrderResponse;

class FaceToFacePayService
{
    private const API_BASE_URL = 'https://api.weixin.qq.com';
    private const CREATE_ORDER_URL = '/wxa/business/f2f/createorderinfo';
    private const QUERY_ORDER_URL = '/wxa/business/f2f/queryorderinfo';
    private const CLOSE_ORDER_URL = '/wxa/business/f2f/closeorderinfo';

    public function __construct(
        private readonly Client $client,
        private readonly FaceToFaceOrderRepository $orderRepository,
        private readonly LoggerInterface $logger,
        private readonly string $appId,
        private readonly string $mchId,
        private readonly string $apiKey
    ) {
    }

    /**
     * 创建面对面收款订单
     */
    public function createOrder(FaceToFaceOrder $order): CreateOrderResponse
    {
        $this->validateOrder($order);

        try {
            $response = $this->sendCreateOrderRequest($order);

            // 更新订单信息
            $order->setCodeUrl($response->getCodeUrl());
            $order->setPrepayId($response->getPrepayId());
            $this->orderRepository->save($order, true);

            $this->logger->info('面对面收款订单创建成功', [
                'out_trade_no' => $order->getOutTradeNo(),
                'prepay_id' => $response->getPrepayId(),
                'code_url' => $response->getCodeUrl(),
            ]);

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('面对面收款订单创建失败', [
                'out_trade_no' => $order->getOutTradeNo(),
                'error' => $e->getMessage(),
            ]);

            throw new WechatPayException('创建订单失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 查询订单状态
     */
    public function queryOrder(string $outTradeNo): QueryOrderResponse
    {
        try {
            $response = $this->sendQueryOrderRequest($outTradeNo);

            // 更新本地订单状态
            $order = $this->orderRepository->findByOutTradeNo($outTradeNo);
            if ($order !== null) {
                $this->updateOrderFromResponse($order, $response);
                $this->orderRepository->save($order, true);
            }

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('查询面对面收款订单失败', [
                'out_trade_no' => $outTradeNo,
                'error' => $e->getMessage(),
            ]);

            throw new WechatPayException('查询订单失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 关闭订单
     */
    public function closeOrder(string $outTradeNo): bool
    {
        try {
            $this->sendCloseOrderRequest($outTradeNo);

            // 更新本地订单状态
            $order = $this->orderRepository->findByOutTradeNo($outTradeNo);
            if ($order !== null) {
                $order->setTradeState(TradeState::CLOSED->value);
                $order->setTradeStateDesc('订单已关闭');
                $this->orderRepository->save($order, true);
            }

            $this->logger->info('面对面收款订单关闭成功', [
                'out_trade_no' => $outTradeNo,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('关闭面对面收款订单失败', [
                'out_trade_no' => $outTradeNo,
                'error' => $e->getMessage(),
            ]);

            throw new WechatPayException('关闭订单失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 轮询查询订单状态直到完成或超时
     */
    public function pollOrderStatus(string $outTradeNo, int $maxAttempts = 30, int $intervalSeconds = 2): QueryOrderResponse
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $response = $this->queryOrder($outTradeNo);

            // 检查是否为最终状态
            $tradeState = $this->getTradeStateFromValue($response->getTradeState());
            if ($tradeState !== null && $tradeState->isFinal()) {
                return $response;
            }

            $attempts++;
            if ($attempts < $maxAttempts) {
                sleep($intervalSeconds);
            }
        }

        throw new WechatPayException('订单状态查询超时');
    }

    /**
     * 发送创建订单请求
     */
    private function sendCreateOrderRequest(FaceToFaceOrder $order): CreateOrderResponse
    {
        $params = $this->buildCreateOrderParams($order);
        $response = $this->sendHttpRequest(self::CREATE_ORDER_URL, $params);
        $data = $this->parseAndValidateResponse($response);

        return new CreateOrderResponse($data);
    }

    /**
     * 构建创建订单参数
     *
     * @return array<string, mixed>
     */
    private function buildCreateOrderParams(FaceToFaceOrder $order): array
    {
        $params = [
            'appid' => $this->appId,
            'mchid' => $this->mchId,
            'out_trade_no' => $order->getOutTradeNo(),
            'total_fee' => $order->getTotalFee(),
            'currency' => $order->getCurrency(),
            'body' => $order->getBody(),
        ];

        $params = $this->addOptionalParams($params, $order);

        $params['sign'] = $this->generateSign($params);

        return $params;
    }

    /**
     * 添加可选参数
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function addOptionalParams(array $params, FaceToFaceOrder $order): array
    {
        if ($order->getOpenid() !== null) {
            $params['openid'] = $order->getOpenid();
        }

        if ($order->getAttach() !== null) {
            $params['attach'] = $order->getAttach();
        }

        if ($order->getGoodsTag() !== null) {
            $params['goods_tag'] = $order->getGoodsTag();
        }

        if ($order->getLimitPay() !== null) {
            $params['limit_pay'] = $order->getLimitPay();
        }

        return $params;
    }

    /**
     * 发送 HTTP 请求
     *
     * @param array<string, mixed> $params
     */
    private function sendHttpRequest(string $endpoint, array $params): \Psr\Http\Message\ResponseInterface
    {
        return $this->client->post(self::API_BASE_URL . $endpoint, [
            'json' => $params,
            'timeout' => 30,
        ]);
    }

    /**
     * 解析和验证响应
     *
     * @return array<string, mixed>
     */
    private function parseAndValidateResponse(\Psr\Http\Message\ResponseInterface $response): array
    {
        $content = $response->getBody()->getContents();
        $data = json_decode($content, true);
        if (!is_array($data)) {
            throw new WechatPayException('响应解析失败');
        }

        // 类型转换：确保数组键为字符串，值为混合类型
        /** @var array<string, mixed> $typedData */
        $typedData = $data;

        if (isset($typedData['errcode']) && is_numeric($typedData['errcode']) && (int)$typedData['errcode'] !== 0) {
            $errMsg = isset($typedData['errmsg']) && is_string($typedData['errmsg']) ? $typedData['errmsg'] : '未知错误';
            $errCode = is_numeric($typedData['errcode']) ? (int)$typedData['errcode'] : 0;
            throw new WechatPayException($errMsg, $errCode);
        }

        return $typedData;
    }

    /**
     * 发送查询订单请求
     */
    private function sendQueryOrderRequest(string $outTradeNo): QueryOrderResponse
    {
        $params = [
            'appid' => $this->appId,
            'mchid' => $this->mchId,
            'out_trade_no' => $outTradeNo,
        ];

        $params['sign'] = $this->generateSign($params);
        $response = $this->sendHttpRequest(self::QUERY_ORDER_URL, $params);
        $data = $this->parseAndValidateResponse($response);

        return new QueryOrderResponse($data);
    }

    /**
     * 发送关闭订单请求
     */
    private function sendCloseOrderRequest(string $outTradeNo): void
    {
        $params = [
            'appid' => $this->appId,
            'mchid' => $this->mchId,
            'out_trade_no' => $outTradeNo,
        ];

        $params['sign'] = $this->generateSign($params);
        $response = $this->sendHttpRequest(self::CLOSE_ORDER_URL, $params);
        $this->parseAndValidateResponse($response);
    }

    /**
     * 生成签名
     *
     * @param array<string, mixed> $params
     */
    private function generateSign(array $params): string
    {
        // 过滤空值
        $filteredParams = [];
        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null) {
                $filteredParams[$key] = $value;
            }
        }
        $params = $filteredParams;

        // 按键名排序
        ksort($params);

        // 生成签名字符串
        $signString = urldecode(http_build_query($params));
        $signString .= '&key=' . $this->apiKey;

        return strtoupper(md5($signString));
    }

    /**
     * 验证订单参数
     */
    private function validateOrder(FaceToFaceOrder $order): void
    {
        if ($order->getOutTradeNo() === '') {
            throw new WechatPayException('商户订单号不能为空');
        }

        if ($order->getTotalFee() <= 0) {
            throw new WechatPayException('支付金额必须大于0');
        }

        if ($order->getBody() === '') {
            throw new WechatPayException('商品描述不能为空');
        }

        // 检查订单是否已存在
        $existingOrder = $this->orderRepository->findByOutTradeNo($order->getOutTradeNo());
        if ($existingOrder !== null) {
            throw new WechatPayException('订单号已存在');
        }
    }

    /**
     * 从响应更新订单信息
     */
    private function updateOrderFromResponse(FaceToFaceOrder $order, QueryOrderResponse $response): void
    {
        $order->setTradeState($response->getTradeState());
        $order->setTradeStateDesc($response->getTradeStateDesc());
        $order->setTransactionId($response->getTransactionId());
        $order->setBankType($response->getBankType());
        $order->setSuccessTime($response->getSuccessTime());
        $order->setPayType($response->getPayType());
        $order->setTimeEnd($response->getTimeEnd());

        if ($response->getErrCode() !== null) {
            $order->setErrCode($response->getErrCode());
            $order->setErrMsg($response->getErrMsg());
        }
    }

    /**
     * 从值获取交易状态枚举
     */
    private function getTradeStateFromValue(?string $value): ?TradeState
    {
        if ($value === null) {
            return null;
        }

        return match ($value) {
            'NOTPAY' => TradeState::NOTPAY,
            'SUCCESS' => TradeState::SUCCESS,
            'REFUND' => TradeState::REFUND,
            'NOTPAYNOT' => TradeState::NOTPAYNOT,
            'CLOSED' => TradeState::CLOSED,
            'PAYERROR' => TradeState::PAYERROR,
            'USERPAYING' => TradeState::USERPAYING,
            default => null,
        };
    }
}
