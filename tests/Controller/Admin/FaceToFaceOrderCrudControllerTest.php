<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use WechatPayFaceToFaceBundle\Controller\Admin\DashboardController;
use WechatPayFaceToFaceBundle\Controller\Admin\FaceToFaceOrderCrudController;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;

#[CoversClass(FaceToFaceOrderCrudController::class)]
#[RunTestsInSeparateProcesses]
final class FaceToFaceOrderCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    public function testGetEntityFqcn(): void
    {
        $this->assertSame(FaceToFaceOrder::class, FaceToFaceOrderCrudController::getEntityFqcn());
    }

    public function testFixtureLoaded(): void
    {
        self::createClientWithDatabase();
        $repository = self::getEntityManager()->getRepository(FaceToFaceOrder::class);
        $this->assertGreaterThan(0, $repository->count([]));
    }

    protected function getControllerService(): FaceToFaceOrderCrudController
    {
        return self::getService(FaceToFaceOrderCrudController::class);
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function onSetUp(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'id_header' => ['ID'];
        yield 'out_trade_no_header' => ['商户订单号'];
        yield 'appid_header' => ['小程序AppID'];
        yield 'mchid_header' => ['商户号'];
        yield 'total_fee_header' => ['支付金额'];
        yield 'currency_header' => ['货币类型'];
        yield 'body_header' => ['商品描述'];
        yield 'code_url_header' => ['收款二维码'];
        yield 'transaction_id_header' => ['微信支付订单号'];
        yield 'trade_state_header' => ['交易状态'];
        yield 'trade_state_desc_header' => ['交易状态描述'];
        yield 'bank_type_header' => ['付款银行'];
        yield 'pay_type_header' => ['支付方式'];
        yield 'created_at_header' => ['创建时间'];
        yield 'updated_at_header' => ['更新时间'];
        yield 'expire_time_header' => ['过期时间'];
        yield 'user_header' => ['用户'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'out_trade_no_field' => ['outTradeNo'];
        yield 'appid_field' => ['appid'];
        yield 'mchid_field' => ['mchid'];
        yield 'total_fee_field' => ['totalFee'];
        yield 'body_field' => ['body'];
        yield 'openid_field' => ['openid'];
        yield 'prepay_id_field' => ['prepayId'];
        yield 'transaction_id_field' => ['transactionId'];
        yield 'trade_state_field' => ['tradeState'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield from self::provideNewPageFields();
    }

    public function testValidationErrors(): void
    {
        $validator = self::getService(ValidatorInterface::class);
        $order = new FaceToFaceOrder();

        $violations = $validator->validate($order);

        $this->assertGreaterThan(0, count($violations));
    }
}
