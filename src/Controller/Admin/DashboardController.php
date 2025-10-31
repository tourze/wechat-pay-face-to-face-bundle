<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;

#[AdminDashboard(routePath: '/wechat-pay-face-to-face/admin', routeName: 'wechat_pay_face_to_face_admin')]
final class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('@EasyAdmin/welcome.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('微信面对面收款');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('面对面订单', 'fa fa-file-invoice', FaceToFaceOrder::class);
    }
}
