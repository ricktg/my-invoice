<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoice')]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $owner = null;

    #[ORM\Column(length: 60)]
    private string $number = '';

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $issueDate;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $issuerCompany = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $recipientCompany = null;

    #[ORM\Column(length: 3)]
    private string $currency = 'CAD';

    #[ORM\Column(length: 5)]
    private string $language = 'en';

    #[ORM\Column(length: 7)]
    private string $referenceMonth = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, InvoiceItem>
     */
    #[Assert\Count(min: 1, minMessage: 'Adicione pelo menos um item de cobrança.')]
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    /**
     * @var Collection<int, InvoiceEmailLog>
     */
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceEmailLog::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sentAt' => 'DESC'])]
    private Collection $emailLogs;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->emailLogs = new ArrayCollection();
        $this->issueDate = new \DateTimeImmutable('today');
        $this->referenceMonth = (new \DateTimeImmutable('today'))->format('Y-m');
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = trim($number);

        return $this;
    }

    public function getIssueDate(): \DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function setIssueDate(\DateTimeImmutable $issueDate): self
    {
        $this->issueDate = $issueDate;

        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): self
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getIssuerCompany(): ?Company
    {
        return $this->issuerCompany;
    }

    public function setIssuerCompany(?Company $issuerCompany): self
    {
        $this->issuerCompany = $issuerCompany;

        return $this;
    }

    public function getRecipientCompany(): ?Company
    {
        return $this->recipientCompany;
    }

    public function setRecipientCompany(?Company $recipientCompany): self
    {
        $this->recipientCompany = $recipientCompany;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = mb_strtoupper(trim($currency));

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = trim($language);

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes !== null ? trim($notes) : null;

        return $this;
    }

    public function getReferenceMonth(): string
    {
        return $this->referenceMonth;
    }

    public function setReferenceMonth(string $referenceMonth): self
    {
        $this->referenceMonth = trim($referenceMonth);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, InvoiceItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(InvoiceItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setInvoice($this);
        }

        return $this;
    }

    public function removeItem(InvoiceItem $item): self
    {
        if ($this->items->removeElement($item) && $item->getInvoice() === $this) {
            $item->setInvoice(null);
        }

        return $this;
    }

    public function getTotalAmount(): string
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += (float) $item->getTotalAmount();
        }

        return number_format($total, 2, '.', '');
    }

    /**
     * @return Collection<int, InvoiceEmailLog>
     */
    public function getEmailLogs(): Collection
    {
        return $this->emailLogs;
    }

    public function addEmailLog(InvoiceEmailLog $emailLog): self
    {
        if (!$this->emailLogs->contains($emailLog)) {
            $this->emailLogs->add($emailLog);
            $emailLog->setInvoice($this);
        }

        return $this;
    }

    public function hasSuccessfulEmailDelivery(): bool
    {
        foreach ($this->emailLogs as $log) {
            if ($log->getStatus() === InvoiceEmailLog::STATUS_SUCCESS) {
                return true;
            }
        }

        return false;
    }

    public function getLastSuccessfulEmailSentAt(): ?\DateTimeImmutable
    {
        foreach ($this->emailLogs as $log) {
            if ($log->getStatus() === InvoiceEmailLog::STATUS_SUCCESS) {
                return $log->getSentAt();
            }
        }

        return null;
    }

    #[Assert\Callback]
    public function validateDailyRateUniqueness(ExecutionContextInterface $context): void
    {
        $dailyRateCount = 0;
        foreach ($this->items as $item) {
            if ($item->getBillingType() === InvoiceItem::BILLING_DAILY_RATE) {
                $dailyRateCount++;
            }
        }

        if ($dailyRateCount > 1) {
            $context->buildViolation('A invoice pode ter no máximo um item do tipo Daily rate.')
                ->atPath('items')
                ->addViolation();
        }
    }
}
