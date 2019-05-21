<?php

namespace Comsa\BookingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="Comsa\BookingBundle\Repository\OptionRepository")
 * @ORM\Table("booking_options")
 */
class Option implements Translatable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"option", "reservation"})
     */
    private $id;

    /**
     * @var Collection
     * @ORM\ManyToMany(targetEntity="Comsa\BookingBundle\Entity\ReservableInterval", mappedBy="options")
     * @Serializer\Groups({"option"})
     */
    private $intervals;

    /**
     * @Gedmo\Translatable()
     * @ORM\Column(type="string", length=255)
     * @Serializer\Groups({"option", "reservation"})
     */
    private $title;

    /**
     * @Gedmo\Translatable()
     * @var string $description
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"option", "reservation"})
     */
    private $description;

    /**
     * @Gedmo\Locale()
     */
    private $locale;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     * @Serializer\Groups({"option", "reservation"})
     */
    private $price;

    public function __construct()
    {
        $this->intervals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description = null): void
    {
        $this->description = $description;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getIntervals(): Collection
    {
        return $this->intervals;
    }

    public function addInterval(ReservableInterval $interval): self
    {
        if (!$this->intervals->contains($interval)) {
            $this->intervals[] = $interval;
            $interval->addOption($this);
        }

        return $this;
    }

    public function removeInterval(ReservableInterval $interval): self
    {
        if ($this->intervals->contains($interval)) {
            $this->intervals->removeElement($interval);
            $interval->removeOption($this);
        }

        return $this;
    }
}
