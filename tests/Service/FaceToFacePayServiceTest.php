<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;
use WechatPayFaceToFaceBundle\Enum\TradeState;
use WechatPayFaceToFaceBundle\Exception\WechatPayException;
use WechatPayFaceToFaceBundle\Repository\FaceToFaceOrderRepository;
use WechatPayFaceToFaceBundle\Response\CreateOrderResponse;
use WechatPayFaceToFaceBundle\Response\QueryOrderResponse;
use WechatPayFaceToFaceBundle\Service\FaceToFacePayService;

#[CoversClass(FaceToFacePayService::class)]
class FaceToFacePayServiceTest extends TestCase
{
    private FaceToFacePayService $service;
    private Client&\PHPUnit\Framework\MockObject\MockObject $client;
    private FaceToFaceOrderRepository&\PHPUnit\Framework\MockObject\MockObject $repository;
    private LoggerInterface&\PHPUnit\Framework\MockObject\MockObject $logger;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->repository = $this->createMock(FaceToFaceOrderRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new FaceToFacePayService(
            $this->client,
            $this->repository,
            $this->logger,
            'wx-test-appid',
            'test-mchid',
            'test-api-key'
        );
    }

    public function testValidateOrder(): void
    {
        $order = new FaceToFaceOrder();
        $order->setBody('测试商品');

        $this->expectException(WechatPayException::class);
        $this->expectExceptionMessage('商户订单号不能为空');

        $this->service->createOrder($order);
    }

    public function testValidateOrderAmount(): void
    {
        $order = new FaceToFaceOrder();
        $order->setOutTradeNo('TEST123');
        $order->setTotalFee(0); // 金额为0
        $order->setBody('测试商品');
        
        $this->expectException(WechatPayException::class);
        $this->expectExceptionMessage('支付金额必须大于0');

        $this->service->createOrder($order);
    }

    public function testValidateOrderBody(): void
    {
        $order = new FaceToFaceOrder();
        $order->setOutTradeNo('TEST123');
        $order->setTotalFee(100);
        $order->setBody(''); // 商品描述为空
        
        $this->expectException(WechatPayException::class);
        $this->expectExceptionMessage('商品描述不能为空');

        $this->service->createOrder($order);
    }

    public function testCloseOrder(): void
    {
        $outTradeNo = 'TEST123';
        $order = new FaceToFaceOrder();
        $order->setOutTradeNo($outTradeNo);

        // Mock repository 返回订单
        $this->repository->expects($this->once())
            ->method('findByOutTradeNo')
            ->with($outTradeNo)
            ->willReturn($order);

        // Mock repository 保存订单
        $this->repository->expects($this->once())
            ->method('save')
            ->with($order, true);

        // Mock HTTP 响应
        $response = new Response(200, [], '{"errcode": 0, "errmsg": "ok"}');
        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        // Mock logger
        $this->logger->expects($this->once())
            ->method('info')
            ->with('面对面收款订单关闭成功', ['out_trade_no' => $outTradeNo]);

        $result = $this->service->closeOrder($outTradeNo);

        $this->assertTrue($result);
        $this->assertSame(TradeState::CLOSED->value, $order->getTradeState());
        $this->assertSame('订单已关闭', $order->getTradeStateDesc());
    }

    public function testCloseOrderWithoutExistingOrder(): void
    {
        $outTradeNo = 'TEST123';

        // Mock repository 返回 null（订单不存在）
        $this->repository->expects($this->once())
            ->method('findByOutTradeNo')
            ->with($outTradeNo)
            ->willReturn(null);

        // Mock repository 保存方法不应该被调用
        $this->repository->expects($this->never())
            ->method('save');

        // Mock HTTP 响应
        $response = new Response(200, [], '{"errcode": 0, "errmsg": "ok"}');
        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        // Mock logger
        $this->logger->expects($this->once())
            ->method('info')
            ->with('面对面收款订单关闭成功', ['out_trade_no' => $outTradeNo]);

        $result = $this->service->closeOrder($outTradeNo);

        $this->assertTrue($result);
    }

    public function testCloseOrderFailure(): void
    {
        $outTradeNo = 'TEST123';

        // Mock HTTP 错误响应
        $response = new Response(200, [], '{"errcode": 500, "errmsg": "订单不存在"}');
        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        // Mock logger
        $this->logger->expects($this->once())
            ->method('error')
            ->with('关闭面对面收款订单失败', static::callback(fn($context) => is_array($context)));

        $this->expectException(WechatPayException::class);
        $this->expectExceptionMessage('关闭订单失败: 订单不存在');

        $this->service->closeOrder($outTradeNo);
    }

    public function testCloseOrderException(): void
    {
        $outTradeNo = 'TEST123';

        // Mock HTTP 异常
        $this->client->expects($this->once())
            ->method('post')
            ->willThrowException(new \Exception('Network error'));

        // Mock logger
        $this->logger->expects($this->once())
            ->method('error')
            ->with('关闭面对面收款订单失败', static::callback(fn($context) => is_array($context)));

        $this->expectException(WechatPayException::class);
        $this->expectExceptionMessage('关闭订单失败: Network error');

        $this->service->closeOrder($outTradeNo);
    }

    public function testCreateOrder(): void
    {
        $order = new FaceToFaceOrder();
        $order->setOutTradeNo('TEST123');
        $order->setTotalFee(100);
        $order->setBody('测试商品');
        $order->setOpenid('test-openid');
        $order->setAttach('test-attach');
        $order->setGoodsTag('test-goods-tag');
        $order->setLimitPay('no_credit');

        // Mock repository 检查订单不存在
        $this->repository->expects($this->once())
            ->method('findByOutTradeNo')
            ->with('TEST123')
            ->willReturn(null);

        // Mock repository 保存订单
        $this->repository->expects($this->once())
            ->method('save')
            ->with($order, true);

        // Mock HTTP 响应
        $responseData = [
            'errcode' => 0,
            'errmsg' => 'ok',
            'code_url' => 'weixin://wxpay/bizpayurl?pr=xxxxx',
            'prepay_id' => 'test_prepay_id_123'
        ];
        $jsonBody = json_encode($responseData) !== false ? json_encode($responseData) : '{}';
        /** @var non-empty-string $jsonBody */
        $response = new Response(200, [], $jsonBody);
        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        // Mock logger
        $this->logger->expects($this->once())
            ->method('info')
            ->with('面对面收款订单创建成功', static::callback(fn($context) => is_array($context)));

        $result = $this->service->createOrder($order);

        $this->assertInstanceOf(CreateOrderResponse::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertSame('weixin://wxpay/bizpayurl?pr=xxxxx', $result->getCodeUrl());
        $this->assertSame('test_prepay_id_123', $result->getPrepayId());

        // 验证订单已更新
        $this->assertSame('weixin://wxpay/bizpayurl?pr=xxxxx', $order->getCodeUrl());
        $this->assertSame('test_prepay_id_123', $order->getPrepayId());
    }

    public function testCreateOrderDuplicate(): void
    {
        $order = new FaceToFaceOrder();
        $order->setOutTradeNo('TEST123');
        $order->setTotalFee(100);
        $order->setBody('测试商品');

        // Mock repository 返回已存在的订单
        $existingOrder = new FaceToFaceOrder();
        $this->repository->expects($this->once())
            ->method('findByOutTradeNo')
            ->with('TEST123')
            ->willReturn($existingOrder);

        // Mock repository 保存方法不应该被调用
        $this->repository->expects($this->never())
            ->method('save');

        // Mock HTTP 请求不应该被调用
        $this->client->expects($this->never())
            ->method('post');

        $this->expectException(WechatPayException::class);
        $this->expectExceptionMessage('订单号已存在');

        $this->service->createOrder($order);
    }

    public function testCreateOrderApiError(): void
    {
        $order = new FaceToFaceOrder();
        $order->setOutTradeNo('TEST123');
        $order->setTotalFee(100);
        $order->setBody('测试商品');

        // Mock repository 检查订单不存在
        $this->repository->expects($this->once())
            ->method('findByOutTradeNo')
            ->with('TEST123')
            ->willReturn(null);

        // Mock HTTP 错误响应
        $responseData = [
            'errcode' => 500,
            'errmsg' => '参数错误'
        ];
        $jsonBody = json_encode($responseData) !== false ? json_encode($responseData) : '{}';
        /** @var non-empty-string $jsonBody */
        $response = new Response(200, [], $jsonBody);
        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        // Mock logger
        $this->logger->expects($this->once())
            ->method('error')
            ->with('面对面收款订单创建失败', static::callback(fn($context) => is_array($context)));

        $this->expectException(WechatPayException::class);
        $this->expectExceptionMessage('创建订单失败: 参数错误');

        $this->service->createOrder($order);
    }

    public function testCreateOrderException(): void
    {
        $order = new FaceToFaceOrder();
        $order->setOutTradeNo('TEST123');
        $order->setTotalFee(100);
        $order->setBody('测试商品');

        // Mock repository 检查订单不存在
        $this->repository->expects($this->once())
            ->method('findByOutTradeNo')
            ->with('TEST123')
            ->willReturn(null);

        // Mock HTTP 异常
        $this->client->expects($this->once())
            ->method('post')
            ->willThrowException(new \Exception('Network error'));

        // Mock logger
        $this->logger->expects($this->once())
            ->method('error')
            ->with('面对面收款订单创建失败', static::callback(fn($context) => is_array($context)));

        $this->expectException(WechatPayException::class);
        $this->expectExceptionMessage('创建订单失败: Network error');

        $this->service->createOrder($order);
    }

    /**
     * @param array<string, mixed> $orderData
     */
    #[TestWith([[
        'out_trade_no' => 'TEST123',
        'total_fee' => 100,
        'body' => '测试商品',
    ]], 'minimal order')]
    #[TestWith([[
        'out_trade_no' => 'TEST456',
        'total_fee' => 200,
        'body' => '测试商品2',
        'openid' => 'test-openid-456',
    ]], 'order with openid')]
    #[TestWith([[
        'out_trade_no' => 'TEST789',
        'total_fee' => 300,
        'body' => '测试商品3',
        'openid' => 'test-openid-789',
        'attach' => 'test-attach',
        'goods_tag' => 'test-goods-tag',
        'limit_pay' => 'no_credit',
    ]], 'order with all optional fields')]
    public function testCreateOrderWithDataProvider(array $orderData): void
    {
        $order = new FaceToFaceOrder();
        $this->populateOrderFromData($order, $orderData);

        // Mock repository 检查订单不存在
        $this->repository->expects($this->once())
            ->method('findByOutTradeNo')
            ->with($orderData['out_trade_no'])
            ->willReturn(null);

        // Mock repository 保存订单
        $this->repository->expects($this->once())
            ->method('save')
            ->with($order, true);

        // Mock HTTP 响应
        $responseData = [
            'errcode' => 0,
            'errmsg' => 'ok',
            'code_url' => 'weixin://wxpay/bizpayurl?pr=xxxxx',
            'prepay_id' => 'test_prepay_id_123'
        ];
        $jsonBody = json_encode($responseData) !== false ? json_encode($responseData) : '{}';
        /** @var non-empty-string $jsonBody */
        $response = new Response(200, [], $jsonBody);
        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        // Mock logger
        $this->logger->expects($this->once())
            ->method('info')
            ->with('面对面收款订单创建成功', static::callback(fn($context) => is_array($context)));

        $result = $this->service->createOrder($order);

        $this->assertInstanceOf(CreateOrderResponse::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    public function testPollOrderStatusSuccess(): void
    {
        $outTradeNo = 'TEST123';

        // 创建 Mock QueryOrderResponse
        $successResponse = new QueryOrderResponse([
            'errcode' => 0,
            'errmsg' => 'ok',
            'out_trade_no' => $outTradeNo,
            'trade_state' => 'SUCCESS',
            'trade_state_desc' => '支付成功',
            'transaction_id' => 'wx_transaction_123'
        ]);

        // Mock queryOrder 方法返回成功状态
        $serviceMock = $this->getMockBuilder(FaceToFacePayService::class)
            ->setConstructorArgs([
                $this->client,
                $this->repository,
                $this->logger,
                'wx-test-appid',
                'test-mchid',
                'test-api-key'
            ])
            ->onlyMethods(['queryOrder'])
            ->getMock();

        $serviceMock->expects($this->once())
            ->method('queryOrder')
            ->with($outTradeNo)
            ->willReturn($successResponse);

        $result = $serviceMock->pollOrderStatus($outTradeNo, 1, 1);

        $this->assertInstanceOf(QueryOrderResponse::class, $result);
        $this->assertSame('SUCCESS', $result->getTradeState());
        $this->assertTrue($result->isPaid());
    }

    public function testPollOrderStatusFinalState(): void
    {
        $outTradeNo = 'TEST123';

        // 测试不同的最终状态
        $finalStates = [
            'SUCCESS' => '支付成功',
            'CLOSED' => '已关闭',
            'PAYERROR' => '支付失败',
            'REFUND' => '转入退款',
        ];

        foreach ($finalStates as $state => $desc) {
            $finalResponse = new QueryOrderResponse([
                'errcode' => 0,
                'errmsg' => 'ok',
                'out_trade_no' => $outTradeNo,
                'trade_state' => $state,
                'trade_state_desc' => $desc
            ]);

            $serviceMock = $this->getMockBuilder(FaceToFacePayService::class)
                ->setConstructorArgs([
                    $this->client,
                    $this->repository,
                    $this->logger,
                    'wx-test-appid',
                    'test-mchid',
                    'test-api-key'
                ])
                ->onlyMethods(['queryOrder'])
                ->getMock();

            $serviceMock->expects($this->once())
                ->method('queryOrder')
                ->with($outTradeNo)
                ->willReturn($finalResponse);

            $result = $serviceMock->pollOrderStatus($outTradeNo, 1, 1);

            $this->assertInstanceOf(QueryOrderResponse::class, $result);
            $this->assertSame($state, $result->getTradeState());
            $this->assertTrue($result->isFinalState());
        }
    }

    public function testPollOrderStatusTimeout(): void
    {
        $outTradeNo = 'TEST123';

        // 创建 Mock QueryOrderResponse（非最终状态）
        $pendingResponse = new QueryOrderResponse([
            'errcode' => 0,
            'errmsg' => 'ok',
            'out_trade_no' => $outTradeNo,
            'trade_state' => 'NOTPAY',
            'trade_state_desc' => '未支付'
        ]);

        $serviceMock = $this->getMockBuilder(FaceToFacePayService::class)
            ->setConstructorArgs([
                $this->client,
                $this->repository,
                $this->logger,
                'wx-test-appid',
                'test-mchid',
                'test-api-key'
            ])
            ->onlyMethods(['queryOrder'])
            ->getMock();

        // Mock queryOrder 总是返回非最终状态
        $serviceMock->expects($this->exactly(2))
            ->method('queryOrder')
            ->with($outTradeNo)
            ->willReturn($pendingResponse);

        $this->expectException(WechatPayException::class);
        $this->expectExceptionMessage('订单状态查询超时');

        $serviceMock->pollOrderStatus($outTradeNo, 2, 1);
    }

    public function testPollOrderStatusCustomParameters(): void
    {
        $outTradeNo = 'TEST123';

        $successResponse = new QueryOrderResponse([
            'errcode' => 0,
            'errmsg' => 'ok',
            'out_trade_no' => $outTradeNo,
            'trade_state' => 'SUCCESS',
            'trade_state_desc' => '支付成功'
        ]);

        $serviceMock = $this->getMockBuilder(FaceToFacePayService::class)
            ->setConstructorArgs([
                $this->client,
                $this->repository,
                $this->logger,
                'wx-test-appid',
                'test-mchid',
                'test-api-key'
            ])
            ->onlyMethods(['queryOrder'])
            ->getMock();

        // 第一次调用返回非最终状态，第二次返回成功状态
        $pendingResponse = new QueryOrderResponse([
            'errcode' => 0,
            'errmsg' => 'ok',
            'out_trade_no' => $outTradeNo,
            'trade_state' => 'USERPAYING',
            'trade_state_desc' => '用户支付中'
        ]);

        $serviceMock->expects($this->exactly(2))
            ->method('queryOrder')
            ->with($outTradeNo)
            ->willReturnOnConsecutiveCalls($pendingResponse, $successResponse);

        // 使用自定义参数：最多5次尝试，间隔3秒
        $result = $serviceMock->pollOrderStatus($outTradeNo, 5, 3);

        $this->assertInstanceOf(QueryOrderResponse::class, $result);
        $this->assertSame('SUCCESS', $result->getTradeState());
        $this->assertTrue($result->isPaid());
    }

      /**
     * @param 'SUCCESS'|'CLOSED'|'PAYERROR'|'NOTPAY'|'USERPAYING' $tradeState
     */
    #[TestWith(['SUCCESS', '支付成功', true], 'success state')]
    #[TestWith(['CLOSED', '已关闭', true], 'failed state closed')]
    #[TestWith(['PAYERROR', '支付失败', true], 'failed state payerror')]
    #[TestWith(['NOTPAY', '未支付', false], 'pending state')]
    #[TestWith(['USERPAYING', '用户支付中', false], 'paying state')]
    public function testPollOrderStatusWithProvider(string $tradeState, string $desc, bool $isFinal): void
    {
        $outTradeNo = 'TEST123';

        $response = new QueryOrderResponse([
            'errcode' => 0,
            'errmsg' => 'ok',
            'out_trade_no' => $outTradeNo,
            'trade_state' => $tradeState,
            'trade_state_desc' => $desc
        ]);

        $serviceMock = $this->getMockBuilder(FaceToFacePayService::class)
            ->setConstructorArgs([
                $this->client,
                $this->repository,
                $this->logger,
                'wx-test-appid',
                'test-mchid',
                'test-api-key'
            ])
            ->onlyMethods(['queryOrder'])
            ->getMock();

        if ($isFinal) {
            // 如果是最终状态，应该只调用一次
            $serviceMock->expects($this->once())
                ->method('queryOrder')
                ->with($outTradeNo)
                ->willReturn($response);

            $result = $serviceMock->pollOrderStatus($outTradeNo, 1, 1);

            $this->assertInstanceOf(QueryOrderResponse::class, $result);
            $this->assertSame($tradeState, $result->getTradeState());
            $this->assertTrue($result->isFinalState());
        } else {
            // 如果不是最终状态，应该超时
            $serviceMock->expects($this->exactly(1))
                ->method('queryOrder')
                ->with($outTradeNo)
                ->willReturn($response);

            $this->expectException(WechatPayException::class);
            $this->expectExceptionMessage('订单状态查询超时');

            $serviceMock->pollOrderStatus($outTradeNo, 1, 1);
        }
    }

    public function testQueryOrder(): void
    {
        $outTradeNo = 'TEST123';
        $order = new FaceToFaceOrder();
        $order->setOutTradeNo($outTradeNo);

        // Mock repository 返回订单
        $this->repository->expects($this->once())
            ->method('findByOutTradeNo')
            ->with($outTradeNo)
            ->willReturn($order);

        // Mock repository 保存订单
        $this->repository->expects($this->once())
            ->method('save')
            ->with($order, true);

        // Mock HTTP 响应
        $responseData = [
            'errcode' => 0,
            'errmsg' => 'ok',
            'out_trade_no' => $outTradeNo,
            'trade_state' => 'SUCCESS',
            'trade_state_desc' => '支付成功',
            'transaction_id' => 'wx_transaction_123',
            'bank_type' => 'CFT',
            'success_time' => '20240101120000',
            'pay_type' => 'MICROPAY',
            'time_end' => 1704110400
        ];
        $jsonBody = json_encode($responseData) !== false ? json_encode($responseData) : '{}';
        /** @var non-empty-string $jsonBody */
        $response = new Response(200, [], $jsonBody);
        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $result = $this->service->queryOrder($outTradeNo);

        $this->assertInstanceOf(QueryOrderResponse::class, $result);
        $this->assertSame('SUCCESS', $result->getTradeState());
        $this->assertSame('支付成功', $result->getTradeStateDesc());
        $this->assertSame('wx_transaction_123', $result->getTransactionId());
        $this->assertSame('CFT', $result->getBankType());
        $this->assertSame('20240101120000', $result->getSuccessTime());
        $this->assertSame('MICROPAY', $result->getPayType());
        $this->assertSame(1704110400, $result->getTimeEnd());
        $this->assertTrue($result->isPaid());

        // 验证订单已更新
        $this->assertSame('SUCCESS', $order->getTradeState());
        $this->assertSame('支付成功', $order->getTradeStateDesc());
        $this->assertSame('wx_transaction_123', $order->getTransactionId());
        $this->assertSame('CFT', $order->getBankType());
        $this->assertSame('20240101120000', $order->getSuccessTime());
        $this->assertSame('MICROPAY', $order->getPayType());
        $this->assertSame(1704110400, $order->getTimeEnd());
    }

    public function testQueryOrderWithoutExistingOrder(): void
    {
        $outTradeNo = 'TEST123';

        // Mock repository 返回 null（订单不存在）
        $this->repository->expects($this->once())
            ->method('findByOutTradeNo')
            ->with($outTradeNo)
            ->willReturn(null);

        // Mock repository 保存方法不应该被调用
        $this->repository->expects($this->never())
            ->method('save');

        // Mock HTTP 响应
        $responseData = [
            'errcode' => 0,
            'errmsg' => 'ok',
            'out_trade_no' => $outTradeNo,
            'trade_state' => 'NOTPAY',
            'trade_state_desc' => '未支付'
        ];
        $jsonBody = json_encode($responseData) !== false ? json_encode($responseData) : '{}';
        /** @var non-empty-string $jsonBody */
        $response = new Response(200, [], $jsonBody);
        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $result = $this->service->queryOrder($outTradeNo);

        $this->assertInstanceOf(QueryOrderResponse::class, $result);
        $this->assertSame('NOTPAY', $result->getTradeState());
        $this->assertFalse($result->isPaid());
    }

    public function testQueryOrderApiError(): void
    {
        $outTradeNo = 'TEST123';

        // Mock HTTP 错误响应
        $responseData = [
            'errcode' => 500,
            'errmsg' => '订单不存在'
        ];
        $jsonBody = json_encode($responseData) !== false ? json_encode($responseData) : '{}';
        /** @var non-empty-string $jsonBody */
        $response = new Response(200, [], $jsonBody);
        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        // Mock logger
        $this->logger->expects($this->once())
            ->method('error')
            ->with('查询面对面收款订单失败', static::callback(fn($context) => is_array($context)));

        $this->expectException(WechatPayException::class);
        $this->expectExceptionMessage('查询订单失败: 订单不存在');

        $this->service->queryOrder($outTradeNo);
    }

    public function testQueryOrderException(): void
    {
        $outTradeNo = 'TEST123';

        // Mock HTTP 异常
        $this->client->expects($this->once())
            ->method('post')
            ->willThrowException(new \Exception('Network error'));

        // Mock logger
        $this->logger->expects($this->once())
            ->method('error')
            ->with('查询面对面收款订单失败', static::callback(fn($context) => is_array($context)));

        $this->expectException(WechatPayException::class);
        $this->expectExceptionMessage('查询订单失败: Network error');

        $this->service->queryOrder($outTradeNo);
    }

    public function testQueryOrderWithErrorFields(): void
    {
        $outTradeNo = 'TEST123';
        $order = new FaceToFaceOrder();
        $order->setOutTradeNo($outTradeNo);

        // Mock repository 返回订单
        $this->repository->expects($this->once())
            ->method('findByOutTradeNo')
            ->with($outTradeNo)
            ->willReturn($order);

        // Mock repository 保存订单
        $this->repository->expects($this->once())
            ->method('save')
            ->with($order, true);

        // Mock HTTP 响应（包含错误字段）
        $responseData = [
            'errcode' => 0,
            'errmsg' => 'ok',
            'out_trade_no' => $outTradeNo,
            'trade_state' => 'PAYERROR',
            'trade_state_desc' => '支付失败',
            'err_code' => 'PAYERROR'  // 注意：QueryOrderResponse 目前不处理这个字段
        ];
        $jsonBody = json_encode($responseData) !== false ? json_encode($responseData) : '{}';
        /** @var non-empty-string $jsonBody */
        $response = new Response(200, [], $jsonBody);
        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $result = $this->service->queryOrder($outTradeNo);

        $this->assertInstanceOf(QueryOrderResponse::class, $result);
        $this->assertSame('PAYERROR', $result->getTradeState());
        $this->assertTrue($result->isFailed());

        // 验证订单已更新错误信息
        $this->assertSame('PAYERROR', $order->getTradeState());
        $this->assertSame('支付失败', $order->getTradeStateDesc());
        // 注意：err_code 字段目前不会被 QueryOrderResponse 处理，所以这些字段会保持为 null
        $this->assertNull($order->getErrCode());
        $this->assertNull($order->getErrMsg());
    }

    /**
     * @param array<string, mixed> $responseData
     */
    #[TestWith([[
        'errcode' => 0,
        'errmsg' => 'ok',
        'trade_state' => 'SUCCESS',
        'trade_state_desc' => '支付成功'
    ], 'SUCCESS', true], 'success state')]
    #[TestWith([[
        'errcode' => 0,
        'errmsg' => 'ok',
        'trade_state' => 'CLOSED',
        'trade_state_desc' => '已关闭'
    ], 'CLOSED', false], 'failed state')]
    #[TestWith([[
        'errcode' => 0,
        'errmsg' => 'ok',
        'trade_state' => 'NOTPAY',
        'trade_state_desc' => '未支付'
    ], 'NOTPAY', false], 'pending state')]
    #[TestWith([[
        'errcode' => 0,
        'errmsg' => 'ok',
        'trade_state' => 'USERPAYING',
        'trade_state_desc' => '用户支付中'
    ], 'USERPAYING', false], 'paying state')]
    public function testQueryOrderWithStatesProvider(array $responseData, string $expectedState, bool $isPaid): void
    {
        $outTradeNo = 'TEST123';

        // Mock HTTP 响应
        $jsonBody = json_encode($responseData) !== false ? json_encode($responseData) : '{}';
        /** @var non-empty-string $jsonBody */
        $response = new Response(200, [], $jsonBody);
        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $result = $this->service->queryOrder($outTradeNo);

        $this->assertInstanceOf(QueryOrderResponse::class, $result);
        $this->assertSame($expectedState, $result->getTradeState());
        $this->assertSame($isPaid, $result->isPaid());
    }

    /**
     * @param array<string, mixed> $orderData
     */
    private function populateOrderFromData(FaceToFaceOrder $order, array $orderData): void
    {
        // 使用PHPUnit断言确保键存在
        $this->assertArrayHasKey('out_trade_no', $orderData);
        $this->assertArrayHasKey('total_fee', $orderData);
        $this->assertArrayHasKey('body', $orderData);

        // 使用@var注解指定类型，避免类型转换问题
        /** @var string $outTradeNo */
        $outTradeNo = $orderData['out_trade_no'];
        /** @var int $totalFee */
        $totalFee = $orderData['total_fee'];
        /** @var string $body */
        $body = $orderData['body'];

        $order->setOutTradeNo($outTradeNo);
        $order->setTotalFee($totalFee);
        $order->setBody($body);

        // 设置可选字段
        $this->setOpenidIfProvided($order, $orderData);
        $this->setAttachIfProvided($order, $orderData);
        $this->setGoodsTagIfProvided($order, $orderData);
        $this->setLimitPayIfProvided($order, $orderData);
    }

    /**
     * @param array<string, mixed> $orderData
     */
    private function setOpenidIfProvided(FaceToFaceOrder $order, array $orderData): void
    {
        if (isset($orderData['openid'])) {
            $value = $orderData['openid'];
            if (is_string($value) && $value !== '') {
                $order->setOpenid($value);
            }
        }
    }

    /**
     * @param array<string, mixed> $orderData
     */
    private function setAttachIfProvided(FaceToFaceOrder $order, array $orderData): void
    {
        if (isset($orderData['attach'])) {
            $value = $orderData['attach'];
            if (is_string($value) && $value !== '') {
                $order->setAttach($value);
            }
        }
    }

    /**
     * @param array<string, mixed> $orderData
     */
    private function setGoodsTagIfProvided(FaceToFaceOrder $order, array $orderData): void
    {
        if (isset($orderData['goods_tag'])) {
            $value = $orderData['goods_tag'];
            if (is_string($value) && $value !== '') {
                $order->setGoodsTag($value);
            }
        }
    }

    /**
     * @param array<string, mixed> $orderData
     */
    private function setLimitPayIfProvided(FaceToFaceOrder $order, array $orderData): void
    {
        if (isset($orderData['limit_pay'])) {
            $value = $orderData['limit_pay'];
            if (is_string($value) && $value !== '') {
                $order->setLimitPay($value);
            }
        }
    }
}
