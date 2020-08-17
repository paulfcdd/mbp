<?php

namespace App\Entity;

use App\Repository\ConversionsRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @ORM\Entity(repositoryClass=ConversionsRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Conversions implements EntityInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="conversions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $mediabuyer;

    /**
     * @ORM\ManyToOne(targetEntity=TeasersClick::class, inversedBy="conversions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $teaserClick;

    /**
     * @ORM\ManyToOne(targetEntity=Partners::class, inversedBy="conversions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $affilate;

    /**
     * @ORM\ManyToOne(targetEntity=Sources::class, inversedBy="conversions")
     * @ORM\JoinColumn(nullable=true)
     */
    private $source;

    /**
     * @ORM\ManyToOne(targetEntity=News::class, inversedBy="conversions")
     * @ORM\JoinColumn(nullable=true)
     */
    private $news;

    /**
     * @ORM\ManyToOne(targetEntity=TeasersSubGroup::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $subgroup;

    /**
     * @ORM\ManyToOne(targetEntity=Country::class, inversedBy="conversions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $country;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=4)
     * @Range(
     *      min = 1,
     *      max = 999999.9999,
     * )
     */
    private $amount;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=4)
     * @Range(
     *      min = 1,
     *      max = 999999.9999,
     * )
     */
    private $amountRub;

    /**
     * @ORM\ManyToOne(targetEntity=CurrencyList::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $currency;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Design", inversedBy="conversions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $design;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Algorithm", inversedBy="conversions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $algorithm;

    /**
     * @var UuidInterface
     * @ORM\Column(name="uuid", type="uuid")
     */
    private $uuid;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $is_deleted = false;

    /**
     * @ORM\ManyToOne(targetEntity=ConversionStatus::class, inversedBy="conversions")
     */
    private $status;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMediabuyer(): ?User
    {
        return $this->mediabuyer;
    }

    public function setMediabuyer(?User $mediabuyer): self
    {
        $this->mediabuyer = $mediabuyer;

        return $this;
    }

    public function getTeaserClick(): ?TeasersClick
    {
        return $this->teaserClick;
    }

    public function setTeaserClick(TeasersClick $teaserClick): self
    {
        $this->teaserClick = $teaserClick;

        return $this;
    }

    public function getAffilate(): ?Partners
    {
        return $this->affilate;
    }

    public function setAffilate(?Partners $affilate): self
    {
        $this->affilate = $affilate;

        return $this;
    }

    public function getSource(): ?Sources
    {
        return $this->source;
    }

    public function setSource(?Sources $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getNews(): ?News
    {
        return $this->news;
    }

    public function setNews(?News $news): self
    {
        $this->news = $news;

        return $this;
    }

    public function getSubgroup(): ?TeasersSubGroup
    {
        return $this->subgroup;
    }

    public function setSubgroup(?TeasersSubGroup $subgroup): self
    {
        $this->subgroup = $subgroup;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmountRub(): ?string
    {
        return $this->amountRub;
    }

    public function setAmountRub(string $amountRub): self
    {
        $this->amountRub = $amountRub;

        return $this;
    }

    public function getCurrency(): ?CurrencyList
    {
        return $this->currency;
    }

    public function setCurrency(?CurrencyList $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getDesign(): Design
    {
        return $this->design;
    }

    public function setDesign(Design $design): self
    {
        $this->design = $design;

        return $this;
    }

    public function getAlgorithm(): Algorithm
    {
        return $this->algorithm;
    }

    public function setAlgorithm(Algorithm $algorithm): self
    {
        $this->algorithm = $algorithm;

        return $this;
    }

    /**
     * @return UuidInterface
     */
    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(UuidInterface $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    //TODO раскомментировать при выходе из тестового режима
    /**
     * @ORM\PrePersist()
     * @return $this
     */
    public function setCreatedAt($createdAt): self
    {
        $this->createdAt = $createdAt instanceof \DateTime ? $createdAt: new \DateTime();

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    //TODO раскомментировать при выходе из тестового режима
    /**
     * @ORM\PreUpdate()
     * @return $this
     */
    public function setUpdatedAt($updateAt): self
    {
        $this->updatedAt = $updateAt instanceof \DateTime ? $updateAt: new \DateTime();

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->is_deleted;
    }

    public function setIsDeleted(bool $is_deleted): self
    {
        $this->is_deleted = $is_deleted;

        return $this;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addConstraint(new UniqueEntity([
            'fields' => 'click_id'
        ]));
    }

    public function getStatus(): ?ConversionStatus
    {
        return $this->status;
    }

    public function setStatus(?ConversionStatus $status): self
    {
        $this->status = $status;

        return $this;
    }
}
