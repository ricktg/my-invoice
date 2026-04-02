<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\InvoiceItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class InvoiceItemTest extends TestCase
{
    public function testDefaultValuesAndMutators(): void
    {
        $item = new InvoiceItem();

        self::assertSame(InvoiceItem::BILLING_DAILY_RATE, $item->getBillingType());
        self::assertSame('1.00', $item->getQuantity());
        self::assertSame('0.00', $item->getUnitPrice());

        $item
            ->setBillingType(InvoiceItem::BILLING_ONE_OFF)
            ->setDescription('  Reembolso de viagem  ')
            ->setQuantity('3')
            ->setUnitPrice('125.40');

        self::assertSame(InvoiceItem::BILLING_ONE_OFF, $item->getBillingType());
        self::assertSame('Reembolso de viagem', $item->getDescription());
        self::assertSame('3', $item->getQuantity());
        self::assertSame('125.40', $item->getUnitPrice());
        self::assertSame('376.20', $item->getTotalAmount());
        self::assertSame('Cobrança única / reembolso', $item->getBillingTypeLabel());
        self::assertSame('3', $item->getDisplayQuantity());
    }

    public function testHourlyRateHelpers(): void
    {
        $item = (new InvoiceItem())
            ->setBillingType(InvoiceItem::BILLING_HOURLY_RATE)
            ->setQuantity('7.50')
            ->setUnitPrice('110.00');

        self::assertTrue($item->isHourlyRate());
        self::assertFalse($item->isDailyRate());
        self::assertSame('Hourly rate', $item->getBillingTypeLabel());
        self::assertSame('7.5', $item->getDisplayQuantity());
        self::assertSame('825.00', $item->getTotalAmount());
    }

    public function testDailyRateQuantityDisplaysAsInteger(): void
    {
        $item = (new InvoiceItem())
            ->setBillingType(InvoiceItem::BILLING_DAILY_RATE)
            ->setQuantity('22.00');

        self::assertSame('22', $item->getDisplayQuantity());
        self::assertSame('Daily rate', $item->getBillingTypeLabel());
    }

    public function testValidatorBuildsViolationWhenQuantityIsNotPositive(): void
    {
        $item = (new InvoiceItem())
            ->setBillingType(InvoiceItem::BILLING_HOURLY_RATE)
            ->setQuantity('0');

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects(self::once())->method('atPath')->with('quantity')->willReturnSelf();
        $builder->expects(self::once())->method('addViolation');

        /** @var ExecutionContextInterface&MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context
            ->expects(self::once())
            ->method('buildViolation')
            ->with('A quantidade deve ser maior que zero.')
            ->willReturn($builder);

        $item->validateQuantityForBillingType($context);
    }

    public function testValidatorBuildsViolationWhenDailyRateHasDecimalQuantity(): void
    {
        $item = (new InvoiceItem())
            ->setBillingType(InvoiceItem::BILLING_DAILY_RATE)
            ->setQuantity('10.5');

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects(self::once())->method('atPath')->with('quantity')->willReturnSelf();
        $builder->expects(self::once())->method('addViolation');

        /** @var ExecutionContextInterface&MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context
            ->expects(self::once())
            ->method('buildViolation')
            ->with('Para Daily rate, a quantidade deve ser um número inteiro de dias.')
            ->willReturn($builder);

        $item->validateQuantityForBillingType($context);
    }

    public function testValidatorDoesNotBuildViolationWhenHourlyRateQuantityIsValid(): void
    {
        $item = (new InvoiceItem())
            ->setBillingType(InvoiceItem::BILLING_HOURLY_RATE)
            ->setQuantity('8.75');

        /** @var ExecutionContextInterface&MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())->method('buildViolation');

        $item->validateQuantityForBillingType($context);
    }
}
