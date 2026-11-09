<?php

namespace App\Entity;

use App\Enum\ItemCategory;
use App\Repository\ItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[ORM\Table(name: 'items')]
#[ORM\Index(name: 'idx_items_category', columns: ['category'])]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 150)]
    private string $name = '';

    #[ORM\Column(length: 20, enumType: ItemCategory::class)]
    #[Assert\NotNull]
    private ItemCategory $category = ItemCategory::Autre;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Loan> */
    #[ORM\OneToMany(targetEntity: Loan::class, mappedBy: 'item')]
    private Collection $loans;

    public function __construct(string $name = '', ItemCategory $category = ItemCategory::Autre)
    {
        $this->name = $name;
        $this->category = $category;
        $this->createdAt = new \DateTimeImmutable();
        $this->loans = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = trim((string) $name);

        return $this;
    }

    public function getCategory(): ItemCategory
    {
        return $this->category;
    }

    public function setCategory(ItemCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes !== null && trim($notes) !== '' ? trim($notes) : null;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, Loan> */
    public function getLoans(): Collection
    {
        return $this->loans;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
