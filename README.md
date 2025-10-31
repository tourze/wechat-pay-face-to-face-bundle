# 微信小程序面对面收款 Bundle

[English](README.md) | [中文](README.zh-CN.md)

这个 Symfony Bundle 提供了微信小程序面对面收款功能的完整集成，支持线下扫码收款场景。

## 功能特性

- ✅ 创建面对面收款订单
- ✅ 查询订单支付状态
- ✅ 关闭未支付订单
- ✅ 订单状态轮询
- ✅ 过期订单自动清理
- ✅ 订单状态同步
- ✅ 管理后台集成
- ✅ 完整的 API 接口
- ✅ 命令行工具

## 安装

### 1. 使用 Composer 安装

```bash
composer require tourze/wechat-pay-face-to-face-bundle
```

### 2. 启用 Bundle

在 `config/bundles.php` 文件中添加：

```php
return [
    // ...
    WechatPayFaceToFaceBundle\WechatPayFaceToFaceBundle::class => ['all' => true],
];
```

### 3. 配置环境变量

在 `.env` 文件中添加微信支付配置：

```env
# 微信小程序面对面收款配置
WECHAT_PAY_FACE_TO_FACE_APP_ID=your_app_id
WECHAT_PAY_FACE_TO_FACE_MCH_ID=your_mch_id
WECHAT_PAY_FACE_TO_FACE_API_KEY=your_api_key
```

### 4. 导入路由

在 `config/routes.yaml` 中导入：

```yaml
wechat_pay_face_to_face:
    resource: '@WechatPayFaceToFaceBundle/config/routes.yaml'
```

## 配置

### 基础配置

```yaml
# config/packages/wechat_pay_face_to_face.yaml
wechat_pay_face_to_face:
    app_id: '%env(WECHAT_PAY_FACE_TO_FACE_APP_ID)%'
    mch_id: '%env(WECHAT_PAY_FACE_TO_FACE_MCH_ID)%'
    api_key: '%env(WECHAT_PAY_FACE_TO_FACE_API_KEY)%'
    # 可选配置
    sandbox: false # 是否使用沙箱环境
    timeout: 30   # 请求超时时间（秒）
```

### 数据库配置

Bundle 会自动创建所需的数据库表。确保已配置 Doctrine：

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        # ... 你的数据库配置
    orm:
        # ... 你的 ORM 配置
        mappings:
            WechatPayFaceToFaceBundle:
                type: attribute
                dir: '%kernel.project_dir%/vendor/tourze/wechat-pay-face-to-face-bundle/src/Entity'
                prefix: 'WechatPayFaceToFaceBundle\Entity'
```

## API 接口

### 1. 创建收款订单

```http
POST /api/wechat-pay-face-to-face/create-order
Content-Type: application/json

{
    "out_trade_no": "ORDER123456789",
    "total_fee": 100,
    "body": "测试商品",
    "appid": "wx1234567890abcdef",
    "mchid": "1900000100",
    "currency": "CNY",
    "openid": "o1234567890abcdef",
    "expire_minutes": 30
}
```

**响应：**
```json
{
    "success": true,
    "data": {
        "out_trade_no": "ORDER123456789",
        "code_url": "weixin://wxpay/bizpayurl?pr=xxxxx",
        "prepay_id": "wx1234567890abcdef",
        "total_fee": 100,
        "currency": "CNY",
        "body": "测试商品",
        "expire_time": 1640995200
    }
}
```

### 2. 查询订单状态

```http
GET /api/wechat-pay-face-to-face/query-order/ORDER123456789
```

**响应：**
```json
{
    "success": true,
    "data": {
        "out_trade_no": "ORDER123456789",
        "trade_state": "SUCCESS",
        "trade_state_desc": "支付成功",
        "transaction_id": "4200001234567890123456789",
        "is_paid": true,
        "is_failed": false,
        "is_final_state": true
    }
}
```

### 3. 关闭订单

```http
POST /api/wechat-pay-face-to-face/close-order/ORDER123456789
```

**响应：**
```json
{
    "success": true,
    "message": "订单关闭成功"
}
```

### 4. 轮询订单状态

```http
GET /api/wechat-pay-face-to-face/poll-order-status/ORDER123456789?max_attempts=30&interval_seconds=2
```

### 5. 查询订单列表

```http
GET /api/wechat-pay-face-to-face/orders?limit=20&offset=0
```

### 6. 查询单个订单

```http
GET /api/wechat-pay-face-to-face/order/ORDER123456789
```

## 命令行工具

### 清理过期订单

```bash
# 清理过期订单
php bin/console wechat-pay:face-to-face:cleanup-expired

# 预演模式（不实际关闭订单）
php bin/console wechat-pay:face-to-face:cleanup-expired --dry-run

# 指定批处理大小
php bin/console wechat-pay:face-to-face:cleanup-expired --batch-size=50
```

### 同步订单状态

```bash
# 同步最近30分钟内的订单状态
php bin/console wechat-pay:face-to-face:sync-order-status

# 同步最近60分钟内的订单状态
php bin/console wechat-pay:face-to-face:sync-order-status --minutes=60

# 指定批处理大小
php bin/console wechat-pay:face-to-face:sync-order-status --batch-size=100
```

## 服务使用

### 在控制器中使用

```php
use WechatPayFaceToFaceBundle\Service\FaceToFacePayService;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;

class PaymentController extends AbstractController
{
    public function __construct(
        private FaceToFacePayService $faceToFacePayService
    ) {}

    public function createPayment(): JsonResponse
    {
        $order = new FaceToFaceOrder();
        $order->setOutTradeNo('ORDER' . time());
        $order->setTotalFee(100);
        $order->setBody('测试商品');
        
        $response = $this->faceToFacePayService->createOrder($order);
        
        return new JsonResponse([
            'code_url' => $response->getCodeUrl(),
            'prepay_id' => $response->getPrepayId()
        ]);
    }
}
```

### 处理支付通知

```php
use WechatPayFaceToFaceBundle\Service\FaceToFacePayService;

class NotificationController extends AbstractController
{
    public function handleWechatNotify(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $outTradeNo = $data['out_trade_no'] ?? null;
        
        if ($outTradeNo) {
            $response = $this->faceToFacePayService->queryOrder($outTradeNo);
            
            if ($response->isPaid()) {
                // 处理支付成功逻辑
                $this->processPaymentSuccess($response);
            }
        }
        
        return new Response('SUCCESS');
    }
}
```

## 交易状态说明

| 状态 | 说明 | 是否最终状态 |
|------|------|-------------|
| NOTPAY | 未支付 | 否 |
| SUCCESS | 支付成功 | 是 |
| REFUND | 转入退款 | 是 |
| NOTPAYNOT | 未支付超时已关闭 | 是 |
| CLOSED | 已关闭 | 是 |
| PAYERROR | 支付失败 | 是 |
| USERPAYING | 用户支付中 | 否 |

## 管理后台

Bundle 集成了 EasyAdmin，提供管理后台界面：

- 订单列表查看
- 订单详情查看
- 订单状态筛选
- 订单搜索功能

访问路径：`/admin` （根据你的 EasyAdmin 配置）

## 错误处理

```php
use WechatPayFaceToFaceBundle\Exception\WechatPayException;

try {
    $response = $this->faceToFacePayService->createOrder($order);
} catch (WechatPayException $e) {
    // 处理微信支付相关错误
    $errorCode = $e->getErrorCode();
    $errorMessage = $e->getMessage();
} catch (\Exception $e) {
    // 处理其他错误
}
```

## 常见错误码

| 错误码 | 说明 | 解决方案 |
|--------|------|----------|
| PARAM_ERROR | 参数错误 | 检查请求参数是否正确 |
| ORDER_NOT_EXIST | 订单不存在 | 确认订单号是否正确 |
| ORDER_PAID | 订单已支付 | 无需重复支付 |
| ORDER_CLOSED | 订单已关闭 | 重新创建订单 |
| APPID_NOT_EXIST | APPID不存在 | 检查APPID配置 |
| MCHID_NOT_EXIST | 商户号不存在 | 检查商户号配置 |
| SIGN_ERROR | 签名错误 | 检查API密钥配置 |

## 最佳实践

1. **订单号管理**：确保订单号全局唯一
2. **过期时间**：设置合理的订单过期时间（建议30分钟内）
3. **状态轮询**：使用合理的轮询间隔（建议2-5秒）
4. **错误重试**：实现适当的错误重试机制
5. **日志记录**：记录关键操作和错误信息
6. **定时任务**：定期清理过期订单和同步状态

## 许可证

MIT License

## 支持

如有问题或建议，请提交 Issue 或 Pull Request。