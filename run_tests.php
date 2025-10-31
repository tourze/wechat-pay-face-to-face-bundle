<?php

declare(strict_types=1);

// 简单的测试验证脚本
require_once __DIR__ . '/../../vendor/autoload.php';

use WechatPayFaceToFaceBundle\Enum\TradeState;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;
use WechatPayFaceToFaceBundle\Exception\WechatPayException;
use WechatPayFaceToFaceBundle\Response\CreateOrderResponse;
use WechatPayFaceToFaceBundle\Response\QueryOrderResponse;

echo "=== 微信面对面收款 Bundle 测试验证 ===\n\n";

/** @var int $testsPassed */
$testsPassed = 0;
/** @var int $testsTotal */
$testsTotal = 0;

function runTest(string $testName, callable $test): void {
    global $testsPassed, $testsTotal;
    /** @var int $testsPassed */
    $testsPassed = $testsPassed;
    /** @var int $testsTotal */
    $testsTotal = $testsTotal;
    $testsTotal++;
    
    try {
        $result = $test();
        if ($result) {
            echo "✓ $testName\n";
            /** @var int $testsPassed */
            $testsPassed = $testsPassed;
            $testsPassed++;
        } else {
            echo "✗ $testName - 测试返回 false\n";
        }
    } catch (Exception $e) {
        echo "✗ $testName - {$e->getMessage()}\n";
    } catch (Error $e) {
        echo "✗ $testName - {$e->getMessage()}\n";
    }
}

// 测试枚举
runTest('TradeState 枚举测试', function () {
    foreach (TradeState::cases() as $state) {
        if ($state->getLabel() === '') {
            return false;
        }
        if ($state->getValue() === '') {
            return false;
        }
    }
    return true;
});

runTest('TradeState isFinal() 方法测试', function () {
    return TradeState::SUCCESS->isFinal() === true && TradeState::NOTPAY->isFinal() === false;
});

runTest('TradeState isSuccess() 方法测试', function () {
    return TradeState::SUCCESS->isSuccess() === true && TradeState::NOTPAY->isSuccess() === false;
});

runTest('TradeState isFailed() 方法测试', function () {
    return TradeState::CLOSED->isFailed() === true && TradeState::NOTPAY->isFailed() === false;
});

// 测试实体
runTest('FaceToFaceOrder 实体测试', function () {
    $order = new FaceToFaceOrder();
    
    if ($order->getId() !== null) {
        return false;
    }
    
    $createdAt = $order->getCreatedAt();
    if ($createdAt === null || $createdAt->getTimestamp() <= 0) {
        return false;
    }

    $updatedAt = $order->getUpdatedAt();
    if ($updatedAt === null || $updatedAt->getTimestamp() <= 0) {
        return false;
    }
    
    return true;
});

runTest('FaceToFaceOrder setter/getter 测试', function () {
    $order = new FaceToFaceOrder();
    
    $order->setOutTradeNo('TEST123');
    $order->setAppid('wx1234567890');
    $order->setMchid('1900000100');
    $order->setTotalFee(100);
    $order->setBody('测试商品');
    
    return $order->getOutTradeNo() === 'TEST123' &&
           $order->getAppid() === 'wx1234567890' &&
           $order->getMchid() === '1900000100' &&
           $order->getTotalFee() === 100 &&
           $order->getBody() === '测试商品';
});

// 测试异常
runTest('WechatPayException 异常测试', function () {
    $exception = new WechatPayException('测试异常', 1001, null, 'TEST_ERROR');
    
    return $exception->getMessage() === '测试异常' &&
           $exception->getCode() === 1001 &&
           $exception->getErrorCode() === 'TEST_ERROR';
});

// 测试响应类
runTest('CreateOrderResponse 响应测试', function () {
    $data = [
        'code_url' => 'weixin://wxpay/bizpayurl?pr=test',
        'prepay_id' => 'test_prepay_id'
    ];
    
    $response = new CreateOrderResponse($data);
    
    return $response->getCodeUrl() === 'weixin://wxpay/bizpayurl?pr=test' &&
           $response->getPrepayId() === 'test_prepay_id' &&
           $response->isSuccess() === true;
});

runTest('QueryOrderResponse 响应测试', function () {
    $data = [
        'out_trade_no' => 'TEST123',
        'trade_state' => 'SUCCESS',
        'transaction_id' => '4200001234567890123456789'
    ];
    
    $response = new QueryOrderResponse($data);
    
    return $response->getOutTradeNo() === 'TEST123' &&
           $response->getTradeState() === 'SUCCESS' &&
           $response->getTransactionId() === '4200001234567890123456789' &&
           $response->isPaid() === true &&
           $response->isFinalState() === true;
});

echo "\n=== 测试结果 ===\n";
echo "通过: $testsPassed / $testsTotal\n";

if ($testsPassed >= $testsTotal) {
    echo "🎉 所有测试通过！Bundle 基本功能正常。\n";
    echo "\n📋 测试通过的功能：\n";
    echo "  ✅ TradeState 枚举类（交易状态管理）\n";
    echo "  ✅ FaceToFaceOrder 实体（订单数据模型）\n";
    echo "  ✅ WechatPayException 异常处理\n";
    echo "  ✅ CreateOrderResponse 创建订单响应\n";
    echo "  ✅ QueryOrderResponse 查询订单响应\n";
    echo "\n📝 注意事项：\n";
    echo "  - 基础类和接口工作正常\n";
    echo "  - 完整功能需要配置数据库和依赖\n";
    echo "  - 建议在 Symfony 环境中进行完整测试\n";
} else {
    echo "❌ 有 " . ($testsTotal - $testsPassed) . " 个测试失败\n";
    exit(1);
}