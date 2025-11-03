<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;

/**
 * 微信面对面收款管理后台菜单提供者
 */
#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('微信支付')) {
            $item->addChild('微信支付')
                ->setAttribute('icon', 'fab fa-weixin');
        }

        $weixinPayMenu = $item->getChild('微信支付');
        if (null === $weixinPayMenu) {
            return;
        }

        // 添加面对面收款管理子菜单
        if (null === $weixinPayMenu->getChild('面对面收款')) {
            $weixinPayMenu->addChild('面对面收款')
                ->setAttribute('icon', 'fas fa-qrcode');
        }

        $faceToFaceMenu = $weixinPayMenu->getChild('面对面收款');
        if (null === $faceToFaceMenu) {
            return;
        }

        // 尝试生成CRUD链接，如果失败则跳过（例如没有Dashboard时）
        try {
            $uri = $this->linkGenerator->getCurdListPage(FaceToFaceOrder::class);
            $faceToFaceMenu->addChild('收款订单')
                ->setUri($uri)
                ->setAttribute('icon', 'fas fa-receipt');
        } catch (\Throwable $e) {
            // 如果无法生成链接（例如没有Dashboard），则跳过添加菜单项
            // 这样业务Bundle就不会因为Dashboard的问题而报错
        }
    }
}
