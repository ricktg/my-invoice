<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InvoiceItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: InvoiceItemRepository::class)]
class InvoiceItem
{
    public const BILLING_DAILY_RATE = 'daily_rate';
    public const BILLING_HOURLY_RATE = 'hourly_rate';
    public const BILLING_ONE_OFF = 'one_off';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Invoice $invoice = null;

    #[ORM\Column(length: 30)]
    private string $billingType = self::BILLING_DAILY_RATE;

    #[ORM\Column(length: 180)]
    private string $description = '';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $quantity = '1.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $unitPrice = '0.00';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getBillingType(): string
    {
        return $this->billingType;
    }

    public function setBillingType(string $billingType): self
    {
        $this->billingType = trim($billingType);

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = trim($description);

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): self
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getTotalAmount(): string
    {
        return number_format((float) $this->quantity * (float) $this->unitPrice, 2, '.', '');
    }

    public function isDailyRate(): bool
    {
        return $this->billingType === self::BILLING_DAILY_RATE;
    }

    public function isHourlyRate(): bool
    {
        return $this->billingType === self::BILLING_HOURLY_RATE;
    }

    public function getBillingTypeLabel(): string
    {
        return match ($this->billingType) {
            self::BILLING_DAILY_RATE => 'Daily rate',
            self::BILLING_HOURLY_RATE => 'Hourly rate',
            default => 'Cobrança única / reembolso',
        };
    }

    public function getDisplayQuantity(): string
    {
        if ($this->isDailyRate()) {
            return (string) (int) round((float) $this->quantity);
        }

        $formatted = rtrim(rtrim(number_format((float) $this->quantity, 2, '.', ''), '0'), '.');

        return $formatted !== '' ? $formatted : '0';
    }

    #[Assert\Callback]
    public function validateQuantityForBillingType(ExecutionContextInterface $context): void
    {
        $quantity = (float) $this->quantity;
        if ($quantity <= 0) {
            $context->buildViolation('A quantidade deve ser maior que zero.')
                ->atPath('quantity')
                ->addViolation();

            return;
        }

        if ($this->isDailyRate() && fmod($quantity, 1.0) !== 0.0) {
            $context->buildViolation('Para Daily rate, a quantidade deve ser um número inteiro de dias.')
                ->atPath('quantity')
                ->addViolation();
        }
    }
}
