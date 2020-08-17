<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Schema\Visitor\Visitor;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="black_list")
 * @ORM\Entity(repositoryClass="App\Repository\BlackListRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class BlackList implements EntityInterface
{
    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="blackList", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $buyer;

    /**
     * @ORM\ManyToOne(targetEntity=Visits::class, inversedBy="blackList")
     * @ORM\JoinColumn(nullable=false, referencedColumnName="uuid"))
     */
    private $visitor;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBuyer(): ?User
    {
        return $this->buyer;
    }

    public function setBuyer(User $buyer): self
    {
        $this->buyer = $buyer;

        return $this;
    }

    public function getVisitor(): ?Visits
    {
        return $this->visitor;
    }

    public function setVisitor(Visits $visitor): self
    {
        $this->visitor = $visitor;

        return $this;
    }

}