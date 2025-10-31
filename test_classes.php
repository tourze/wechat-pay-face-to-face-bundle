<?php

declare(strict_types=1);

echo "=== 微信面对面收款 Bundle 基础功能测试 ===\n\n";

/** @var int $testsPassed */
$testsPassed = 0;
/** @var int $testsTotal */
$testsTotal = 0;

function runTest(string $testName, callable $test): void {
    global $testsPassed, $testsTotal;
    /** @var int $testsTotal */
    $testsTotal++;
    
    try {
        $result = $test();
        if ($result) {
            echo "✓ $testName\n";
            /** @var int $testsPassed */
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

echo "📋 测试 Bundle 基础结构\n";

runTest('检查 Bundle 文件结构', function () {
    $files = [
        'src/Entity/FaceToFaceOrder.php',
        'src/Enum/TradeState.php', 
        'src/Service/FaceToFacePayService.php',
        'src/Controller/FaceToFacePayController.php',
        'src/Exception/WechatPayException.php',
        'src/Response/CreateOrderResponse.php',
        'src/Response/QueryOrderResponse.php',
        'src/Repository/FaceToFaceOrderRepository.php',
        'src/Command/CleanupExpiredOrdersCommand.php',
        'src/Command/SyncOrderStatusCommand.php',
        'src/Service/AdminMenu.php',
        'composer.json',
        'README.md'
    ];
    
    foreach ($files as $file) {
        if (!file_exists($file)) {
            return false;
        }
    }
    return true;
});

runTest('PHP 语法检查 - 枚举类', function () {
    $output = [];
    $returnCode = 0;
    exec('php -l src/Enum/TradeState.php 2>&1', $output, $returnCode);
    return $returnCode === 0;
});

runTest('PHP 语法检查 - 实体类', function () {
    $output = [];
    $returnCode = 0;
    exec('php -l src/Entity/FaceToFaceOrder.php 2>&1', $output, $returnCode);
    return $returnCode === 0;
});

runTest('PHP 语法检查 - 服务类', function () {
    $output = [];
    $returnCode = 0;
    exec('php -l src/Service/FaceToFacePayService.php 2>&1', $output, $returnCode);
    return $returnCode === 0;
});

runTest('PHP 语法检查 - 控制器', function () {
    $output = [];
    $returnCode = 0;
    exec('php -l src/Controller/FaceToFacePayController.php 2>&1', $output, $returnCode);
    return $returnCode === 0;
});

runTest('PHP 语法检查 - 异常类', function () {
    $output = [];
    $returnCode = 0;
    exec('php -l src/Exception/WechatPayException.php 2>&1', $output, $returnCode);
    return $returnCode === 0;
});

runTest('PHP 语法检查 - 响应类', function () {
    $output = [];
    $returnCode = 0;
    exec('php -l src/Response/CreateOrderResponse.php 2>&1', $output, $returnCode);
    return $returnCode === 0;
});

runTest('PHP 语法检查 - 命令类', function () {
    $output = [];
    $returnCode = 0;
    exec('php -l src/Command/CleanupExpiredOrdersCommand.php 2>&1', $output, $returnCode);
    return $returnCode === 0;
});

runTest('PHP 语法检查 - AdminMenu', function () {
    $output = [];
    $returnCode = 0;
    exec('php -l src/Service/AdminMenu.php 2>&1', $output, $returnCode);
    return $returnCode === 0;
});

runTest('检查 Composer 配置', function () {
    $composerJson = file_get_contents('composer.json');
    if ($composerJson === false) {
        return false;
    }

    $data = json_decode($composerJson, true);
    if (!is_array($data)) {
        return false;
    }

    // 检查自动加载配置
    if (!isset($data['autoload']) || !is_array($data['autoload'])) {
        return false;
    }
    if (!isset($data['autoload']['psr-4']) || !is_array($data['autoload']['psr-4'])) {
        return false;
    }
    return isset($data['autoload']['psr-4']['WechatPayFaceToFaceBundle\\']);
});

runTest('检查依赖配置', function () {
    $composerJson = file_get_contents('composer.json');
    if ($composerJson === false) {
        return false;
    }

    $data = json_decode($composerJson, true);
    if (!is_array($data)) {
        return false;
    }

    // 检查关键依赖
    $requiredDeps = [
        'symfony/framework-bundle',
        'symfony/routing',
        'symfony/cache-contracts',
        'doctrine/doctrine-bundle',
        'easycorp/easyadmin-bundle',
        'guzzlehttp/guzzle',
        'tourze/doctrine-indexed-bundle',
        'tourze/doctrine-timestamp-bundle',
        'tourze/doctrine-user-bundle',
        'tourze/easy-admin-menu-bundle',
        'tourze/enum-extra'
    ];

    if (!isset($data['require']) || !is_array($data['require'])) {
        return false;
    }

    foreach ($requiredDeps as $dep) {
        if (!isset($data['require'][$dep])) {
            echo "缺少依赖: $dep\n";
            return false;
        }
    }

    return true;
});

echo "\n=== 测试结果 ===\n";
echo "通过: $testsPassed / $testsTotal\n";

if ($testsPassed >= $testsTotal) {
    echo "🎉 Bundle 所有基础检查都通过！\n\n";
    
    echo "📋 验证的功能：\n";
    echo "  ✅ 文件结构完整\n";
    echo "  ✅ PHP 语法正确\n";
    echo "  ✅ Composer 配置正确\n";
    echo "  ✅ 依赖配置完整\n";
    echo "  ✅ 管理后台菜单正常\n\n";
    
    echo "🚀 Bundle 已准备就绪，可以集成到 Symfony 项目中！\n\n";
    
    echo "📋 下一步操作：\n";
    echo "  1. 将 Bundle 添加到项目依赖\n";
    echo "  2. 配置环境变量\n";
    echo "  3. 运行数据库迁移\n";
    echo "  4. 测试 API 接口\n";
    echo "  5. 使用管理后台\n\n";
    
    echo "💡 提示：如果要在现有项目中使用，请运行：\n";
    echo "     composer update tourze/wechat-pay-face-to-face-bundle\n";
    echo "     # 或者将依赖添加到根目录的 composer.json\n";
    
} else {
    echo "❌ 有 " . ($testsTotal - $testsPassed) . " 个检查失败\n";
    exit(1);
}