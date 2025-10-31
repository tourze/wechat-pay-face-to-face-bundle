<?php

declare(strict_types=1);

// Bootstrap for PHPUnit tests
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];

$autoloadLoaded = false;
foreach ($autoloadPaths as $autoloadPath) {
    if (is_file($autoloadPath)) {
        require_once $autoloadPath;
        $autoloadLoaded = true;
        break;
    }
}

if (!$autoloadLoaded) {
    throw new RuntimeException('Composer autoload.php 未找到，无法启动测试环境。');
}

spl_autoload_register(static function (string $className): void {
    $prefixes = [
        'WechatPayFaceToFaceBundle\\' => 'src/',
        'WechatPayFaceToFaceBundle\\Tests\\' => 'tests/',
    ];

    foreach ($prefixes as $prefix => $directory) {
        if (str_starts_with($className, $prefix)) {
            $relativePath = substr($className, strlen($prefix));
            $relativePath = str_replace('\\', '/', $relativePath);
            $filePath = __DIR__ . '/' . $directory . $relativePath . '.php';

            if (is_file($filePath)) {
                require_once $filePath;
            }

            return;
        }
    }
});

echo "✓ PHPUnit Bootstrap loaded for WechatPayFaceToFaceBundle\n";
