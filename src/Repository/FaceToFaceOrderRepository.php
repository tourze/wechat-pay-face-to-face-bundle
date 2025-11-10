<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use WechatPayFaceToFaceBundle\Entity\FaceToFaceOrder;
use WechatPayFaceToFaceBundle\Enum\TradeState;

/**
 * @extends ServiceEntityRepository<FaceToFaceOrder>
 */
#[AsRepository(entityClass: FaceToFaceOrder::class)]
class FaceToFaceOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaceToFaceOrder::class);
    }

    public function save(FaceToFaceOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FaceToFaceOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据商户订单号查找订单
     */
    public function findByOutTradeNo(string $outTradeNo): ?FaceToFaceOrder
    {
        return $this->findOneBy(['outTradeNo' => $outTradeNo]);
    }

    /**
     * 根据微信支付订单号查找订单
     */
    public function findByTransactionId(string $transactionId): ?FaceToFaceOrder
    {
        return $this->findOneBy(['transactionId' => $transactionId]);
    }

    /**
     * 根据预支付ID查找订单
     */
    public function findByPrepayId(string $prepayId): ?FaceToFaceOrder
    {
        return $this->findOneBy(['prepayId' => $prepayId]);
    }

    /**
     * 查找指定用户的订单
     *
     * @param int|null $userId 用户ID
     * @param int $limit 限制数量
     * @param int $offset 偏移量
     * @return list<FaceToFaceOrder>
     */
    public function findByUserId(?int $userId, int $limit = 50, int $offset = 0): array
    {
        if ($userId === null) {
            return [];
        }

        // 边界条件检查
        if ($limit <= 0) {
            $limit = 50;
        }
        if ($offset < 0) {
            $offset = 0;
        }

        // 限制最大查询数量以防止性能问题
        $limit = min($limit, 1000);

        /** @var FaceToFaceOrder[] $result */
        $result = $this->createQueryBuilder('o')
            ->where('o.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('o.createTime', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return array_values($result);
    }

    /**
     * 查找未支付且未过期的订单
     *
     * @param int $expireSeconds 过期秒数
     * @return list<FaceToFaceOrder>
     */
    public function findUnpaidAndNotExpiredOrders(int $expireSeconds = 600): array
    {
        // 边界条件检查
        if ($expireSeconds <= 0) {
            $expireSeconds = 600;
        }

        $now = time();
        $expireTime = $now - $expireSeconds;

        /** @var FaceToFaceOrder[] $result */
        $result = $this->createQueryBuilder('o')
            ->where('o.tradeState = :tradeState')
            ->andWhere('o.expireTime IS NULL OR o.expireTime > :expireTime')
            ->setParameter('tradeState', TradeState::NOTPAY->value)
            ->setParameter('expireTime', $expireTime)
            ->orderBy('o.createTime', 'ASC')
            ->getQuery()
            ->getResult();

        return array_values($result);
    }

    /**
     * 查找需要关闭的过期订单
     *
     * @return list<FaceToFaceOrder>
     */
    public function findExpiredOrdersToClose(): array
    {
        $now = time();

        /** @var FaceToFaceOrder[] $result */
        $result = $this->createQueryBuilder('o')
            ->where('o.tradeState = :tradeState')
            ->andWhere('o.expireTime IS NOT NULL')
            ->andWhere('o.expireTime <= :now')
            ->setParameter('tradeState', TradeState::NOTPAY->value)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        return array_values($result);
    }
}
