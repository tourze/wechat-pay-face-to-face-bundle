<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Validator;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * 面对面支付订单数据验证器
 */
class FaceToFaceOrderValidator
{
    /**
     * 验证创建订单数据
     *
     * @param array<mixed> $data
     * @return JsonResponse|null 返回错误响应或null（验证通过）
     */
    public function validateCreateOrderData(array $data): ?JsonResponse
    {
        if (!$this->validateRequiredFields($data)) {
            return $this->buildErrorResponse('缺少必填参数', 400);
        }

        if (!$this->validateRequiredFieldTypes($data)) {
            return $this->buildErrorResponse('必填参数类型错误', 400);
        }

        if (!$this->validateOptionalFieldTypes($data)) {
            return $this->buildErrorResponse('可选参数类型错误', 400);
        }

        if (!$this->validateNumericFields($data)) {
            return $this->buildErrorResponse('数值参数错误', 400);
        }

        return null;
    }

    /**
     * 验证必填字段
     *
     * @param array<mixed> $data
     */
    private function validateRequiredFields(array $data): bool
    {
        return isset($data['out_trade_no']) && isset($data['total_fee']) && isset($data['body']);
    }

    /**
     * 验证必填字段类型
     *
     * @param array<mixed> $data
     */
    private function validateRequiredFieldTypes(array $data): bool
    {
        if (!$this->isValidString($data, 'out_trade_no')) {
            return false;
        }

        if (!$this->isValidString($data, 'body')) {
            return false;
        }

        return $this->isValidNumeric($data, 'total_fee');
    }

    /**
     * 验证可选字段类型
     *
     * @param array<mixed> $data
     */
    private function validateOptionalFieldTypes(array $data): bool
    {
        $stringFields = ['appid', 'mchid', 'currency', 'openid', 'attach', 'goods_tag', 'limit_pay'];

        foreach ($stringFields as $field) {
            if (!$this->isValidOptionalString($data, $field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查是否为有效字符串
     *
     * @param array<mixed> $data
     */
    private function isValidString(array $data, string $key): bool
    {
        return isset($data[$key]) && is_string($data[$key]);
    }

    /**
     * 检查是否为有效数值
     *
     * @param array<mixed> $data
     */
    private function isValidNumeric(array $data, string $key): bool
    {
        return isset($data[$key]) && is_numeric($data[$key]);
    }

    /**
     * 检查是否为有效可选字符串
     *
     * @param array<mixed> $data
     */
    private function isValidOptionalString(array $data, string $key): bool
    {
        return !isset($data[$key]) || is_string($data[$key]);
    }

    /**
     * 验证数值字段
     *
     * @param array<mixed> $data
     */
    private function validateNumericFields(array $data): bool
    {
        if (!is_numeric($data['total_fee'])) {
            return false;
        }
        $totalFee = (int) $data['total_fee'];
        if ($totalFee < 0) {
            return false;
        }

        if (isset($data['expire_minutes'])) {
            if (!is_numeric($data['expire_minutes'])) {
                return false;
            }
            $expireMinutes = (int) $data['expire_minutes'];
            if ($expireMinutes < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * 构造错误响应
     */
    private function buildErrorResponse(string $error, int $status, ?string $errorCode = null): JsonResponse
    {
        $responseData = ['error' => $error];
        if ($errorCode !== null) {
            $responseData['error_code'] = $errorCode;
        }
        return new JsonResponse($responseData, $status);
    }
}