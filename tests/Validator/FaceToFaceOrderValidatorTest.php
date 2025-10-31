<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use WechatPayFaceToFaceBundle\Validator\FaceToFaceOrderValidator;

#[CoversClass(FaceToFaceOrderValidator::class)]
final class FaceToFaceOrderValidatorTest extends TestCase
{
    private FaceToFaceOrderValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new FaceToFaceOrderValidator();
    }

    /**
     * 安全地解析 JSON 响应内容
     * @return array<string, mixed>
     */
    private function parseJsonResponse(string|false $content): array
    {
        $this->assertIsString($content, 'Response content should be a string');
        $data = json_decode($content, true);
        $this->assertIsArray($data, 'JSON should decode to an array');
        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * @param array<string, mixed> $validData
     */
    #[TestWith([[
        'out_trade_no' => 'TEST123456',
        'total_fee' => 100,
        'body' => '测试商品',
    ]], 'minimal required fields')]
    #[TestWith([[
        'out_trade_no' => 'TEST789',
        'total_fee' => 200,
        'body' => '测试商品2',
        'appid' => 'wx1234567890',
        'mchid' => '1234567890',
        'currency' => 'CNY',
        'openid' => 'test_openid_123',
        'attach' => 'test_attach',
        'goods_tag' => 'test_goods_tag',
        'limit_pay' => 'no_credit',
        'expire_minutes' => 30,
    ]], 'all optional fields')]
    public function testValidateCreateOrderDataSuccess(array $validData): void
    {
        $result = $this->validator->validateCreateOrderData($validData);

        $this->assertNull($result, '验证应该通过，返回null');
    }

    public function testValidateCreateOrderDataMissingRequiredFields(): void
    {
        $testCases = [
            'missing out_trade_no' => [
                'data' => ['total_fee' => 100, 'body' => '测试商品'],
                'expected_error' => '缺少必填参数',
            ],
            'missing total_fee' => [
                'data' => ['out_trade_no' => 'TEST123', 'body' => '测试商品'],
                'expected_error' => '缺少必填参数',
            ],
            'missing body' => [
                'data' => ['out_trade_no' => 'TEST123', 'total_fee' => 100],
                'expected_error' => '缺少必填参数',
            ],
            'empty data' => [
                'data' => [],
                'expected_error' => '缺少必填参数',
            ],
        ];

        foreach ($testCases as $caseName => $testCase) {
            $result = $this->validator->validateCreateOrderData($testCase['data']);

            $this->assertInstanceOf(JsonResponse::class, $result, $caseName . ' 应该返回错误响应');
            $this->assertSame(400, $result->getStatusCode(), $caseName . ' 应该返回400状态码');

            $responseData = $this->parseJsonResponse($result->getContent());
            $this->assertIsArray($responseData, $caseName . ' 响应数据应该是数组');
            $this->assertSame($testCase['expected_error'], $responseData['error'], $caseName . ' 错误消息不匹配');
        }
    }

    public function testValidateCreateOrderDataInvalidRequiredFieldTypes(): void
    {
        $testCases = [
            'out_trade_no not string' => [
                'data' => ['out_trade_no' => 123, 'total_fee' => 100, 'body' => '测试商品'],
                'expected_error' => '必填参数类型错误',
            ],
            'out_trade_no null' => [
                'data' => ['out_trade_no' => null, 'total_fee' => 100, 'body' => '测试商品'],
                'expected_error' => '缺少必填参数',
            ],
            'total_fee not numeric' => [
                'data' => ['out_trade_no' => 'TEST123', 'total_fee' => 'invalid', 'body' => '测试商品'],
                'expected_error' => '必填参数类型错误',
            ],
            'total_fee boolean' => [
                'data' => ['out_trade_no' => 'TEST123', 'total_fee' => false, 'body' => '测试商品'],
                'expected_error' => '必填参数类型错误',
            ],
            'body not string' => [
                'data' => ['out_trade_no' => 'TEST123', 'total_fee' => 100, 'body' => 456],
                'expected_error' => '必填参数类型错误',
            ],
            'body null' => [
                'data' => ['out_trade_no' => 'TEST123', 'total_fee' => 100, 'body' => null],
                'expected_error' => '缺少必填参数',
            ],
        ];

        foreach ($testCases as $caseName => $testCase) {
            $result = $this->validator->validateCreateOrderData($testCase['data']);

            $this->assertInstanceOf(JsonResponse::class, $result, $caseName . ' 应该返回错误响应');
            $this->assertSame(400, $result->getStatusCode(), $caseName . ' 应该返回400状态码');

            $responseData = $this->parseJsonResponse($result->getContent());
            $this->assertIsArray($responseData, $caseName . ' 响应数据应该是数组');
            $this->assertSame($testCase['expected_error'], $responseData['error'], $caseName . ' 错误消息不匹配');
        }
    }

    public function testValidateCreateOrderDataInvalidOptionalFieldTypes(): void
    {
        $baseData = [
            'out_trade_no' => 'TEST123',
            'total_fee' => 100,
            'body' => '测试商品',
        ];

        $testCases = [
            'appid not string' => [
                'data' => array_merge($baseData, ['appid' => 123]),
                'expected_error' => '可选参数类型错误',
            ],
            'mchid not string' => [
                'data' => array_merge($baseData, ['mchid' => true]),
                'expected_error' => '可选参数类型错误',
            ],
            'currency not string' => [
                'data' => array_merge($baseData, ['currency' => []]),
                'expected_error' => '可选参数类型错误',
            ],
            'openid not string' => [
                'data' => array_merge($baseData, ['openid' => new \stdClass()]),
                'expected_error' => '可选参数类型错误',
            ],
            'attach not string' => [
                'data' => array_merge($baseData, ['attach' => 456.789]),
                'expected_error' => '可选参数类型错误',
            ],
            'goods_tag not string' => [
                'data' => array_merge($baseData, ['goods_tag' => 123]),
                'expected_error' => '可选参数类型错误',
            ],
            'limit_pay not string' => [
                'data' => array_merge($baseData, ['limit_pay' => []]),
                'expected_error' => '可选参数类型错误',
            ],
        ];

        foreach ($testCases as $caseName => $testCase) {
            $result = $this->validator->validateCreateOrderData($testCase['data']);

            $this->assertInstanceOf(JsonResponse::class, $result, $caseName . ' 应该返回错误响应');
            $this->assertSame(400, $result->getStatusCode(), $caseName . ' 应该返回400状态码');

            $responseData = $this->parseJsonResponse($result->getContent());
            $this->assertIsArray($responseData, $caseName . ' 响应数据应该是数组');
            $this->assertSame($testCase['expected_error'], $responseData['error'], $caseName . ' 错误消息不匹配');
        }
    }

    public function testValidateCreateOrderDataInvalidNumericFields(): void
    {
        $baseData = [
            'out_trade_no' => 'TEST123',
            'total_fee' => 100,
            'body' => '测试商品',
        ];

        $testCases = [
            'total_fee negative' => [
                'data' => array_merge($baseData, ['total_fee' => -100]),
                'should_pass' => false,
            ],
            'total_fee zero is valid' => [
                'data' => array_merge($baseData, ['total_fee' => 0]),
                'should_pass' => true,
            ],
            'expire_minutes negative' => [
                'data' => array_merge($baseData, ['expire_minutes' => -30]),
                'should_pass' => false,
            ],
            'expire_minutes zero is valid' => [
                'data' => array_merge($baseData, ['expire_minutes' => 0]),
                'should_pass' => true,
            ],
            'expire_minutes not numeric' => [
                'data' => array_merge($baseData, ['expire_minutes' => 'invalid']),
                'should_pass' => false,
            ],
        ];

        foreach ($testCases as $caseName => $testCase) {
            $result = $this->validator->validateCreateOrderData($testCase['data']);

            if ($testCase['should_pass']) {
                $this->assertNull($result, $caseName . ' 应该通过验证');
            } else {
                $this->assertInstanceOf(JsonResponse::class, $result, $caseName . ' 应该返回错误响应');
                $this->assertSame(400, $result->getStatusCode(), $caseName . ' 应该返回400状态码');

                $responseData = $this->parseJsonResponse($result->getContent());
                $this->assertIsArray($responseData, $caseName . ' 响应数据应该是数组');
                $this->assertSame('数值参数错误', $responseData['error'], $caseName . ' 错误消息不匹配');
            }
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function validNumericDataProvider(): array
    {
        return [
            'positive integer' => [
                'data' => ['out_trade_no' => 'TEST1', 'total_fee' => 100, 'body' => 'test'],
            ],
            'positive float' => [
                'data' => ['out_trade_no' => 'TEST2', 'total_fee' => 99.99, 'body' => 'test'],
            ],
            'string numeric' => [
                'data' => ['out_trade_no' => 'TEST3', 'total_fee' => '150', 'body' => 'test'],
            ],
            'expire_minutes positive' => [
                'data' => ['out_trade_no' => 'TEST4', 'total_fee' => 100, 'body' => 'test', 'expire_minutes' => 30],
            ],
            'expire_minutes zero is valid' => [
                'data' => ['out_trade_no' => 'TEST5', 'total_fee' => 100, 'body' => 'test', 'expire_minutes' => 0],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('validNumericDataProvider')]
    public function testValidateCreateOrderDataWithNumericValues(array $data): void
    {
        $result = $this->validator->validateCreateOrderData($data);

        // 所有这些案例都应该成功，因为验证器允许expire_minutes为0
        $this->assertNull($result, '数值验证应该通过');
    }

    public function testValidateCreateOrderDataBoundaryValues(): void
    {
        $testCases = [
            'total_fee zero is valid' => [
                'data' => ['out_trade_no' => 'TEST1', 'total_fee' => 0, 'body' => 'test'],
                'should_pass' => true,
            ],
            'minimum positive total_fee' => [
                'data' => ['out_trade_no' => 'TEST2', 'total_fee' => 1, 'body' => 'test'],
                'should_pass' => true,
            ],
            'maximum reasonable total_fee' => [
                'data' => ['out_trade_no' => 'TEST3', 'total_fee' => 999999999, 'body' => 'test'],
                'should_pass' => true,
            ],
            'expire_minutes zero is valid' => [
                'data' => ['out_trade_no' => 'TEST4', 'total_fee' => 100, 'body' => 'test', 'expire_minutes' => 0],
                'should_pass' => true,
            ],
            'minimum positive expire_minutes' => [
                'data' => ['out_trade_no' => 'TEST5', 'total_fee' => 100, 'body' => 'test', 'expire_minutes' => 1],
                'should_pass' => true,
            ],
            'maximum reasonable expire_minutes' => [
                'data' => ['out_trade_no' => 'TEST6', 'total_fee' => 100, 'body' => 'test', 'expire_minutes' => 1440], // 24 hours
                'should_pass' => true,
            ],
            'total_fee negative integer' => [
                'data' => ['out_trade_no' => 'TEST7', 'total_fee' => -1, 'body' => 'test'],
                'should_pass' => false,
            ],
            'expire_minutes negative integer' => [
                'data' => ['out_trade_no' => 'TEST8', 'total_fee' => 100, 'body' => 'test', 'expire_minutes' => -1],
                'should_pass' => false,
            ],
        ];

        foreach ($testCases as $caseName => $testCase) {
            $result = $this->validator->validateCreateOrderData($testCase['data']);

            if ($testCase['should_pass']) {
                $this->assertNull($result, $caseName . ' 应该通过验证');
            } else {
                $this->assertInstanceOf(JsonResponse::class, $result, $caseName . ' 应该返回错误响应');
                $this->assertSame(400, $result->getStatusCode(), $caseName . ' 应该返回400状态码');

                $responseData = $this->parseJsonResponse($result->getContent());
                $this->assertSame('数值参数错误', $responseData['error'], $caseName . ' 错误消息不匹配');
            }
        }
    }

    public function testValidateCreateOrderDataComplexScenarios(): void
    {
        $testCases = [
            'all optional string fields with valid values' => [
                'data' => [
                    'out_trade_no' => 'COMPLEX_TEST_1',
                    'total_fee' => 150,
                    'body' => '复杂测试商品',
                    'appid' => 'wx1234567890abcdef',
                    'mchid' => '1234567890',
                    'currency' => 'USD',
                    'openid' => 'complex_openid_123',
                    'attach' => 'complex attachment data',
                    'goods_tag' => 'complex_goods_tag',
                    'limit_pay' => 'no_credit',
                ],
                'should_pass' => true,
            ],
            'mixed valid and invalid fields' => [
                'data' => [
                    'out_trade_no' => 'MIXED_TEST',
                    'total_fee' => 200,
                    'body' => '混合测试',
                    'appid' => 123, // 无效：应该是字符串
                    'openid' => 'valid_openid', // 有效
                    'expire_minutes' => 30, // 有效
                ],
                'should_pass' => false,
                'expected_error' => '可选参数类型错误',
            ],
            'numeric fields with various formats' => [
                'data' => [
                    'out_trade_no' => 'NUMERIC_TEST',
                    'total_fee' => '99.99', // 字符串形式的数字
                    'body' => '数值格式测试',
                    'expire_minutes' => 45.5, // 浮点数
                ],
                'should_pass' => true,
            ],
        ];

        foreach ($testCases as $caseName => $testCase) {
            $result = $this->validator->validateCreateOrderData($testCase['data']);

            if ($testCase['should_pass']) {
                $this->assertNull($result, $caseName . ' 应该通过验证');
            } else {
                $this->assertInstanceOf(JsonResponse::class, $result, $caseName . ' 应该返回错误响应');
                $this->assertSame(400, $result->getStatusCode(), $caseName . ' 应该返回400状态码');

                $responseData = $this->parseJsonResponse($result->getContent());
                $expectedError = $testCase['expected_error'] ?? '数值参数错误';
                $this->assertSame($expectedError, $responseData['error'], $caseName . ' 错误消息不匹配');
            }
        }
    }

    public function testValidateCreateOrderDataEdgeCases(): void
    {
        $testCases = [
            'empty strings for required fields' => [
                'data' => [
                    'out_trade_no' => '',
                    'total_fee' => 100,
                    'body' => '',
                ],
                'should_pass' => true, // 空字符串在类型检查中是有效的字符串
            ],
            'whitespace strings' => [
                'data' => [
                    'out_trade_no' => '   ',
                    'total_fee' => 100,
                    'body' => '   商品描述   ',
                ],
                'should_pass' => true, // 空白字符在类型检查中是有效的字符串
            ],
            'very long strings' => [
                'data' => [
                    'out_trade_no' => str_repeat('A', 1000),
                    'total_fee' => 100,
                    'body' => str_repeat('商品描述', 100),
                ],
                'should_pass' => true, // 验证器不检查长度，只检查类型
            ],
            'numeric as strings' => [
                'data' => [
                    'out_trade_no' => '123',
                    'total_fee' => '456.789',
                    'body' => '789',
                ],
                'should_pass' => true, // 虽然看起来像数字，但是都是有效的字符串类型
            ],
        ];

        foreach ($testCases as $caseName => $testCase) {
            $result = $this->validator->validateCreateOrderData($testCase['data']);

            // 所有测试用例都应该通过验证
            $this->assertNull($result, $caseName . ' 应该通过验证');
        }
    }

    public function testValidateCreateOrderDataSpecialCharacters(): void
    {
        $testCases = [
            'unicode characters' => [
                'data' => [
                    'out_trade_no' => '测试订单号_🎉',
                    'total_fee' => 100,
                    'body' => '测试商品描述 🛍️ with émojis',
                ],
                'should_pass' => true,
            ],
            'special characters in strings' => [
                'data' => [
                    'out_trade_no' => 'TEST@#$%^&*()',
                    'total_fee' => 100,
                    'body' => '商品描述 with symbols: !@#$%^&*()_+-=[]{}|;:,.<>?',
                ],
                'should_pass' => true,
            ],
            'json-like strings' => [
                'data' => [
                    'out_trade_no' => '{"key":"value"}',
                    'total_fee' => 100,
                    'body' => '["array","elements"]',
                ],
                'should_pass' => true,
            ],
        ];

        foreach ($testCases as $caseName => $testCase) {
            $result = $this->validator->validateCreateOrderData($testCase['data']);

            // 所有测试用例都应该通过验证
            $this->assertNull($result, $caseName . ' 应该通过验证');
        }
    }

    public function testPrivateMethodsBehavior(): void
    {
        // 测试私有方法的边界行为通过公共方法间接测试
        $reflection = new \ReflectionClass($this->validator);

        // 验证私有方法的存在
        $this->assertTrue($reflection->hasMethod('validateRequiredFields'));
        $this->assertTrue($reflection->hasMethod('validateRequiredFieldTypes'));
        $this->assertTrue($reflection->hasMethod('validateOptionalFieldTypes'));
        $this->assertTrue($reflection->hasMethod('validateNumericFields'));
        $this->assertTrue($reflection->hasMethod('isValidString'));
        $this->assertTrue($reflection->hasMethod('isValidNumeric'));
        $this->assertTrue($reflection->hasMethod('isValidOptionalString'));
        $this->assertTrue($reflection->hasMethod('buildErrorResponse'));

        // 验证方法都是私有的
        $privateMethods = [
            'validateRequiredFields',
            'validateRequiredFieldTypes',
            'validateOptionalFieldTypes',
            'validateNumericFields',
            'isValidString',
            'isValidNumeric',
            'isValidOptionalString',
            'buildErrorResponse',
        ];

        foreach ($privateMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPrivate(), $methodName . ' 应该是私有方法');
        }
    }
}