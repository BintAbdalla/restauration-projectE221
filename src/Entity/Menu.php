<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom du menu est obligatoire")]
    #[Assert\Length(min: 2, max: 255, minMessage: "Le nom doit contenir au moins {{ limit }} caractères", maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères")]
    private ?string $name = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le prix est obligatoire")]
    #[Assert\Positive(message: "Le prix doit être supérieur à 0")]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private ?bool $archived = false;

    #[ORM\ManyToMany(targetEntity: Burger::class)]
    private Collection $burgers;

    #[ORM\ManyToMany(targetEntity: Complement::class)]
    private Collection $complements;

    public function __construct()
    {
        $this->burgers = new ArrayCollection();
        $this->complements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function isArchived(): ?bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): static
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @return Collection<int, Burger>
     */
    public function getBurgers(): Collection
    {
        return $this->burgers;
    }

    public function addBurger(Burger $burger): static
    {
        if (!$this->burgers->contains($burger)) {
            $this->burgers->add($burger);
        }

        return $this;
    }

    public function removeBurger(Burger $burger): static
    {
        $this->burgers->removeElement($burger);

        return $this;
    }

    /**
     * @return Collection<int, Complement>
     */
    public function getComplements(): Collection
    {
        return $this->complements;
    }

    public function addComplement(Complement $complement): static
    {
        if (!$this->complements->contains($complement)) {
            $this->complements->add($complement);
        }

        return $this;
    }

    public function removeComplement(Complement $complement): static
    {
        $this->complements->removeElement($complement);

        return $this;
    }
}