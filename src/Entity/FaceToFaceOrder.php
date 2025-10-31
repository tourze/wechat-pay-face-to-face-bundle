<?php

declare(strict_types=1);

namespace WechatPayFaceToFaceBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Stringable;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Attribute\CreateUserColumn;
use WechatPayFaceToFaceBundle\Enum\TradeState;

#[ORM\Entity]
#[ORM\Table(name: 'wechat_pay_face_to_face_order', options: ['comment' => '微信面对面收款订单表'])]
class FaceToFaceOrder implements Stringable
{
    use TimestampableAware;

    public function __construct()
    {
        $now = new DateTimeImmutable();
        $this->setCreateTime($now);
        $this->setUpdateTime($now);
    }

    /** @phpstan-ignore-next-line property.unusedType */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    #[IndexColumn]
    #[ORM\Column(length: 64, unique: true, options: ['comment' => '商户订单号'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $outTradeNo = '';

    #[ORM\Column(length: 32, options: ['comment' => '小程序appid'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $appid = '';

    #[ORM\Column(length: 32, options: ['comment' => '商户号'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $mchid = '';

    #[ORM\Column(options: ['comment' => '支付金额（分）'])]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private int $totalFee = 0;

    #[ORM\Column(length: 8, options: ['default' => 'CNY', 'comment' => '货币类型'])]
    #[Assert\Choice(choices: ['CNY'])]
    private string $currency = 'CNY';

    #[ORM\Column(length: 128, options: ['comment' => '商品描述'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $body = '';

    #[ORM\Column(length: 64, nullable: true, options: ['comment' => '用户openid'])]
    #[Assert\Length(max: 64)]
    private ?string $openid = null;

    #[ORM\Column(length: 512, nullable: true, options: ['comment' => '收款二维码链接'])]
    #[Assert\Url]
    private ?string $codeUrl = null;

    #[ORM\Column(length: 64, nullable: true, options: ['comment' => '预支付交易会话标识'])]
    #[Assert\Length(max: 64)]
    private ?string $prepayId = null;

    #[ORM\Column(length: 64, nullable: true, options: ['comment' => '微信支付订单号'])]
    #[Assert\Length(max: 64)]
    private ?string $transactionId = null;

    #[ORM\Column(length: 32, options: ['default' => 'NOTPAY', 'comment' => '交易状态'])]
    #[Assert\Choice(choices: ['NOTPAY', 'SUCCESS', 'REFUND', 'NOTPAYNOT', 'CLOSED', 'PAYERROR', 'USERPAYING'])]
    private string $tradeState = 'NOTPAY';

    #[ORM\Column(length: 256, nullable: true, options: ['comment' => '交易状态描述'])]
    #[Assert\Length(max: 256)]
    private ?string $tradeStateDesc = null;

    #[ORM\Column(length: 32, nullable: true, options: ['comment' => '付款银行'])]
    #[Assert\Length(max: 32)]
    private ?string $bankType = null;

    #[ORM\Column(length: 14, nullable: true, options: ['comment' => '支付完成时间'])]
    #[Assert\Length(max: 14)]
    private ?string $successTime = null;

    #[ORM\Column(length: 16, nullable: true, options: ['comment' => '支付方式'])]
    #[Assert\Length(max: 16)]
    private ?string $payType = null;

    #[ORM\Column(length: 32, nullable: true, options: ['comment' => '子商户appid'])]
    #[Assert\Length(max: 32)]
    private ?string $subAppid = null;

    #[ORM\Column(length: 32, nullable: true, options: ['comment' => '子商户号'])]
    #[Assert\Length(max: 32)]
    private ?string $subMchid = null;

    #[ORM\Column(length: 512, nullable: true, options: ['comment' => '错误信息'])]
    #[Assert\Length(max: 512)]
    private ?string $errMsg = null;

    #[ORM\Column(length: 32, nullable: true, options: ['comment' => '错误代码'])]
    #[Assert\Length(max: 32)]
    private ?string $errCode = null;

    #[ORM\Column(length: 127, nullable: true, options: ['comment' => '附加数据'])]
    #[Assert\Length(max: 127)]
    private ?string $attach = null;

    #[ORM\Column(length: 32, nullable: true, options: ['comment' => '商品标记'])]
    #[Assert\Length(max: 32)]
    private ?string $goodsTag = null;

    #[ORM\Column(length: 32, nullable: true, options: ['comment' => '限定支付方式'])]
    #[Assert\Length(max: 32)]
    private ?string $limitPay = null;

    #[ORM\Column(length: 512, nullable: true, options: ['comment' => '优惠信息'])]
    #[Assert\Length(max: 512)]
    private ?string $promotionInfo = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '订单失效时间'])]
    #[Assert\Positive]
    private ?int $expireTime = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '支付完成时间戳'])]
    #[Assert\Positive]
    private ?int $timeEnd = null;

    #[CreateUserColumn]
    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '创建用户ID'])]
    #[Assert\Positive]
    private ?int $userId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOutTradeNo(): string
    {
        return $this->outTradeNo;
    }

    public function setOutTradeNo(string $outTradeNo): void
    {
        $this->outTradeNo = $outTradeNo;
    }

    public function getAppid(): string
    {
        return $this->appid;
    }

    public function setAppid(string $appid): void
    {
        $this->appid = $appid;
    }

    public function getMchid(): string
    {
        return $this->mchid;
    }

    public function setMchid(string $mchid): void
    {
        $this->mchid = $mchid;
    }

    public function getTotalFee(): int
    {
        return $this->totalFee;
    }

    public function setTotalFee(int $totalFee): void
    {
        if ($totalFee < 0) {
            throw new \InvalidArgumentException('Total fee cannot be negative');
        }
        $this->totalFee = $totalFee;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getOpenid(): ?string
    {
        return $this->openid;
    }

    public function setOpenid(?string $openid): void
    {
        $this->openid = $openid;
    }

    public function getCodeUrl(): ?string
    {
        return $this->codeUrl;
    }

    public function setCodeUrl(?string $codeUrl): void
    {
        $this->codeUrl = $codeUrl;
    }

    public function getPrepayId(): ?string
    {
        return $this->prepayId;
    }

    public function setPrepayId(?string $prepayId): void
    {
        $this->prepayId = $prepayId;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getTradeState(): string
    {
        return $this->tradeState;
    }

    public function setTradeState(string $tradeState): void
    {
        $this->tradeState = $tradeState;
    }

    /**
     * 获取交易状态枚举
     */
    public function getTradeStateEnum(): ?TradeState
    {
        return TradeState::tryFrom($this->tradeState);
    }

    /**
     * 设置交易状态（使用枚举）
     */
    public function setTradeStateEnum(TradeState $tradeState): void
    {
        $this->tradeState = $tradeState->getValue();
    }

    /**
     * 检查是否为最终状态
     */
    public function isTradeStateFinal(): bool
    {
        return $this->getTradeStateEnum()?->isFinal() ?? false;
    }

    /**
     * 检查是否为成功状态
     */
    public function isTradeStateSuccess(): bool
    {
        return $this->getTradeStateEnum()?->isSuccess() ?? false;
    }

    /**
     * 检查是否为失败状态
     */
    public function isTradeStateFailed(): bool
    {
        return $this->getTradeStateEnum()?->isFailed() ?? false;
    }

    /**
     * 检查是否为未支付状态
     */
    public function isUnpaid(): bool
    {
        return $this->tradeState === TradeState::NOTPAY->value;
    }

    /**
     * 检查是否为用户支付中状态
     */
    public function isUserPaying(): bool
    {
        return $this->tradeState === TradeState::USERPAYING->value;
    }

    public function getTradeStateDesc(): ?string
    {
        return $this->tradeStateDesc;
    }

    public function setTradeStateDesc(?string $tradeStateDesc): void
    {
        $this->tradeStateDesc = $tradeStateDesc;
    }

    public function getBankType(): ?string
    {
        return $this->bankType;
    }

    public function setBankType(?string $bankType): void
    {
        $this->bankType = $bankType;
    }

    public function getSuccessTime(): ?string
    {
        return $this->successTime;
    }

    public function setSuccessTime(?string $successTime): void
    {
        $this->successTime = $successTime;
    }

    public function getPayType(): ?string
    {
        return $this->payType;
    }

    public function setPayType(?string $payType): void
    {
        $this->payType = $payType;
    }

    public function getSubAppid(): ?string
    {
        return $this->subAppid;
    }

    public function setSubAppid(?string $subAppid): void
    {
        $this->subAppid = $subAppid;
    }

    public function getSubMchid(): ?string
    {
        return $this->subMchid;
    }

    public function setSubMchid(?string $subMchid): void
    {
        $this->subMchid = $subMchid;
    }

    public function getErrMsg(): ?string
    {
        return $this->errMsg;
    }

    public function setErrMsg(?string $errMsg): void
    {
        $this->errMsg = $errMsg;
    }

    public function getErrCode(): ?string
    {
        return $this->errCode;
    }

    public function setErrCode(?string $errCode): void
    {
        $this->errCode = $errCode;
    }

    public function getAttach(): ?string
    {
        return $this->attach;
    }

    public function setAttach(?string $attach): void
    {
        $this->attach = $attach;
    }

    public function getGoodsTag(): ?string
    {
        return $this->goodsTag;
    }

    public function setGoodsTag(?string $goodsTag): void
    {
        $this->goodsTag = $goodsTag;
    }

    public function getLimitPay(): ?string
    {
        return $this->limitPay;
    }

    public function setLimitPay(?string $limitPay): void
    {
        $this->limitPay = $limitPay;
    }

    public function getPromotionInfo(): ?string
    {
        return $this->promotionInfo;
    }

    public function setPromotionInfo(?string $promotionInfo): void
    {
        $this->promotionInfo = $promotionInfo;
    }

    public function getExpireTime(): ?int
    {
        return $this->expireTime;
    }

    public function setExpireTime(?int $expireTime): void
    {
        if ($expireTime !== null && $expireTime < 0) {
            throw new \InvalidArgumentException('Expire time cannot be negative');
        }
        $this->expireTime = $expireTime;
    }

    public function getTimeEnd(): ?int
    {
        return $this->timeEnd;
    }

    public function setTimeEnd(?int $timeEnd): void
    {
        if ($timeEnd !== null && $timeEnd < 0) {
            throw new \InvalidArgumentException('Time end cannot be negative');
        }
        $this->timeEnd = $timeEnd;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): void
    {
        if ($userId !== null && $userId <= 0) {
            throw new \InvalidArgumentException('User ID must be positive');
        }
        $this->userId = $userId;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->getCreateTime();
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->getUpdateTime();
    }

    public function updateTimestamp(): void
    {
        $this->setUpdateTime(new DateTimeImmutable());
    }

    public function __toString(): string
    {
        return sprintf(
            'FaceToFaceOrder[id=%s, outTradeNo=%s, totalFee=%d, tradeState=%s]',
            $this->id ?? 'null',
            $this->outTradeNo,
            $this->totalFee,
            $this->tradeState
        );
    }
}
