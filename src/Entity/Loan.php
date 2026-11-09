<?php

namespace App\Entity;

use App\Enum\LoanStatus;
use App\Repository\LoanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
#[ORM\Table(name: 'loans')]
#[ORM\Index(name: 'idx_loans_status', columns: ['returned_at', 'due_at'])]
// Un objet ne peut avoir qu'un seul prêt en cours : c'est garanti par la base
// (colonne générée active_item_id, voir la migration et GeneratedColumnSchemaListener).
class Loan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull(message: 'Choisis un objet.')]
    private ?Item $item = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull(message: 'Choisis un ami.')]
    private ?Friend $friend = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: 'Indique la date du prêt.')]
    #[Assert\LessThanOrEqual('today', message: 'La date de prêt ne peut pas être dans le futur.')]
    private ?\DateTimeImmutable $loanedAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\GreaterThanOrEqual(propertyPath: 'loanedAt', message: 'La date de retour doit être après la date de prêt.')]
    private ?\DateTimeImmutable $dueAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $returnedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->loanedAt = new \DateTimeImmutable('today');
    }

    public function getStatus(?\DateTimeImmutable $today = null): LoanStatus
    {
        $today ??= new \DateTimeImmutable('today');

        return match (true) {
            $this->returnedAt !== null => LoanStatus::Rendu,
            $this->dueAt !== null && $this->dueAt < $today => LoanStatus::EnRetard,
            default => LoanStatus::EnCours,
        };
    }

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    /** Jours restants avant la date de retour (négatif si dépassée), null si aucune date prévue. */
    public function daysUntilDue(?\DateTimeImmutable $today = null): ?int
    {
        if ($this->dueAt === null) {
            return null;
        }
        $today ??= new \DateTimeImmutable('today');

        return (int) $today->diff($this->dueAt)->format('%r%a');
    }

    /** Avancement du prêt entre la date de prêt et la date de retour, de 0 à 100. */
    public function progress(?\DateTimeImmutable $today = null): ?int
    {
        if ($this->dueAt === null || $this->loanedAt === null) {
            return null;
        }
        $today ??= new \DateTimeImmutable('today');
        $total = max(1, (int) $this->loanedAt->diff($this->dueAt)->format('%a'));
        $elapsed = (int) $this->loanedAt->diff($today)->format('%r%a');

        return max(0, min(100, (int) round($elapsed / $total * 100)));
    }

    public function markReturned(\DateTimeImmutable $on): void
    {
        if ($this->returnedAt !== null) {
            throw new \LogicException('Ce prêt est déjà rendu.');
        }
        $this->returnedAt = $on < $this->loanedAt ? $this->loanedAt : $on;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function getFriend(): ?Friend
    {
        return $this->friend;
    }

    public function setFriend(?Friend $friend): static
    {
        $this->friend = $friend;

        return $this;
    }

    public function getLoanedAt(): ?\DateTimeImmutable
    {
        return $this->loanedAt;
    }

    public function setLoanedAt(?\DateTimeImmutable $loanedAt): static
    {
        $this->loanedAt = $loanedAt;

        return $this;
    }

    public function getDueAt(): ?\DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function setDueAt(?\DateTimeImmutable $dueAt): static
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    public function getReturnedAt(): ?\DateTimeImmutable
    {
        return $this->returnedAt;
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
}
