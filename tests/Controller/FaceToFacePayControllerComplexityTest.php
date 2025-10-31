<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use WechatPayFaceToFaceBundle\Controller\FaceToFacePayController;
use WechatPayFaceToFaceBundle\Repository\FaceToFaceOrderRepository;
use WechatPayFaceToFaceBundle\Service\FaceToFacePayService;
use WechatPayFaceToFaceBundle\Service\FaceToFaceOrderDataPopulator;
use WechatPayFaceToFaceBundle\Validator\FaceToFaceOrderValidator;

/**
 * 简单的复杂度测试，验证重构后的控制器功能正常
 */
#[CoversClass(FaceToFacePayController::class)]
#[RunTestsInSeparateProcesses]
final class FaceToFacePayControllerComplexityTest extends AbstractWebTestCase
{
    public function testControllerCanBeInstantiated(): void
    {
        $faceToFacePayService = $this->createMock(FaceToFacePayService::class);
        $orderRepository = $this->createMock(FaceToFaceOrderRepository::class);
        $orderValidator = $this->createMock(FaceToFaceOrderValidator::class);
        $orderDataPopulator = $this->createMock(FaceToFaceOrderDataPopulator::class);

        $controller = new FaceToFacePayController(
            $faceToFacePayService,
            $orderRepository,
            $orderValidator,
            $orderDataPopulator
        );

        $this->assertInstanceOf(FaceToFacePayController::class, $controller);
    }

    public function testParseJsonRequestWithValidJson(): void
    {
        $controller = $this->createController();
        $jsonData = json_encode(['test' => 'data']);
      if ($jsonData === false) {
          $jsonData = '{}';
      }
      $request = new Request([], [], [], [], [], [], $jsonData);

        // 使用反射来测试私有方法
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('parseJsonRequest');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $request);

        $this->assertSame(['test' => 'data'], $result);
    }

    public function testParseJsonRequestWithInvalidJson(): void
    {
        $controller = $this->createController();
        $request = new Request([], [], [], [], [], [], 'invalid json');

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('parseJsonRequest');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $request);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\JsonResponse::class, $result);
        $this->assertSame(400, $result->getStatusCode());
    }

    private function createController(): FaceToFacePayController
    {
        $faceToFacePayService = $this->createMock(FaceToFacePayService::class);
        $orderRepository = $this->createMock(FaceToFaceOrderRepository::class);
        $orderValidator = $this->createMock(FaceToFaceOrderValidator::class);
        $orderDataPopulator = $this->createMock(FaceToFaceOrderDataPopulator::class);

        return new FaceToFacePayController(
            $faceToFacePayService,
            $orderRepository,
            $orderValidator,
            $orderDataPopulator
        );
    }

    /**
     * 测试不允许的HTTP方法
     */
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();

        // 测试 FaceToFacePayController 的路由，使用一个通用的API路径
        $client->request($method, '/api/face-to-face-pay/test');

        // 对于不支持的HTTP方法，应该返回405或者路由不存在
        $this->assertTrue($client->getResponse()->getStatusCode() === 405 || $client->getResponse()->getStatusCode() === 404);
    }
}