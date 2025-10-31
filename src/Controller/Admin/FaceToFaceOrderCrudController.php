<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;
use WechatPayFaceToFaceBundle\Enum\TradeState;

/**
 * @extends AbstractCrudController<FaceToFaceOrder>
 */
#[AdminCrud(routePath: '/wechat-pay-face-to-face/order', routeName: 'wechat_pay_face_to_face_order')]
final class FaceToFaceOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FaceToFaceOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('面对面收款订单')
            ->setEntityLabelInPlural('面对面收款订单')
            ->setSearchFields(['outTradeNo', 'body', 'transactionId'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('outTradeNo', '商户订单号'))
            ->add(TextFilter::new('transactionId', '微信支付订单号'))
            ->add(TextFilter::new('body', '商品描述'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm();

        yield TextField::new('outTradeNo', '商户订单号');

        yield TextField::new('appid', '小程序AppID');

        yield TextField::new('mchid', '商户号');

        yield MoneyField::new('totalFee', '支付金额')
            ->setCurrency('CNY')
            ->setNumDecimals(2);

        yield TextField::new('currency', '货币类型')
            ->hideOnForm();

        yield TextField::new('body', '商品描述');

        yield TextField::new('openid', '用户OpenID')
            ->hideOnIndex();

        yield TextField::new('codeUrl', '收款二维码')
            ->hideOnForm();

        yield TextField::new('prepayId', '预支付ID')
            ->hideOnIndex();

        yield TextField::new('transactionId', '微信支付订单号');

        yield ChoiceField::new('tradeState', '交易状态')
            ->setChoices(array_reduce(
                TradeState::cases(),
                function (array $carry, TradeState $case) {
                    $carry[$case->getLabel()] = $case->name;
                    return $carry;
                },
                []
            ))
            ->renderExpanded(false);

        yield TextField::new('tradeStateDesc', '交易状态描述')
            ->hideOnForm();

        yield TextField::new('bankType', '付款银行')
            ->hideOnForm();

        yield TextField::new('payType', '支付方式')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss');

        yield DateTimeField::new('updatedAt', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss');

        yield TextField::new('expireTime', '过期时间')
            ->hideOnForm()
            ->formatValue(function ($value) {
                return is_numeric($value) ? date('Y-m-d H:i:s', (int) $value) : $value;
            });

        yield IntegerField::new('userId', '用户')
            ->hideOnForm();
    }
}
