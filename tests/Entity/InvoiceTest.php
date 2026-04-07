<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Company;
use App\Entity\Invoice;
use App\Entity\InvoiceEmailLog;
use App\Entity\InvoiceItem;
use App\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class InvoiceTest extends TestCase
{
    public function testCoreFieldsAndTotalAmount(): void
    {
        $owner = (new User())->setEmail('owner@example.com');
        $issuer = (new Company())->setName('Issuer')->setType(Company::TYPE_PROVIDER);
        $recipient = (new Company())->setName('Recipient')->setType(Company::TYPE_CLIENT);
        $issueDate = new \DateTimeImmutable('2026-03-01');
        $dueDate = new \DateTimeImmutable('2026-03-11');
        $createdAt = new \DateTimeImmutable('2026-03-01 09:00:00');

        $invoice = (new Invoice())
            ->setOwner($owner)
            ->setNumber('  INV-2026-03  ')
            ->setIssueDate($issueDate)
            ->setDueDate($dueDate)
            ->setIssuerCompany($issuer)
            ->setRecipientCompany($recipient)
            ->setCurrency(' usd ')
            ->setReferenceMonth(' 2026-03 ')
            ->setNotes('  Trabalho mensal  ')
            ->setCreatedAt($createdAt);

        $item1 = (new InvoiceItem())->setDescription('Serviços')->setQuantity('22')->setUnitPrice('800.00');
        $item2 = (new InvoiceItem())->setDescription('Reembolso')->setBillingType(InvoiceItem::BILLING_ONE_OFF)->setQuantity('1')->setUnitPrice('120.50');

        $invoice->addItem($item1)->addItem($item2);

        self::assertSame($owner, $invoice->getOwner());
        self::assertSame('INV-2026-03', $invoice->getNumber());
        self::assertSame($issueDate, $invoice->getIssueDate());
        self::assertSame($dueDate, $invoice->getDueDate());
        self::assertSame($issuer, $invoice->getIssuerCompany());
        self::assertSame($recipient, $invoice->getRecipientCompany());
        self::assertSame('USD', $invoice->getCurrency());
        self::assertSame('en', $invoice->getLanguage());
        self::assertSame('2026-03', $invoice->getReferenceMonth());
        self::assertSame('Trabalho mensal', $invoice->getNotes());
        self::assertSame($createdAt, $invoice->getCreatedAt());
        self::assertSame('17720.50', $invoice->getTotalAmount());
        self::assertCount(2, $invoice->getItems());
        self::assertSame($invoice, $item1->getInvoice());
        self::assertSame($invoice, $item2->getInvoice());
    }

    public function testLanguageCanBeChanged(): void
    {
        $invoice = (new Invoice())
            ->setLanguage('pt-BR');

        self::assertSame('pt-BR', $invoice->getLanguage());
    }

    public function testRemoveItemDetachesRelation(): void
    {
        $invoice = new Invoice();
        $item = (new InvoiceItem())->setDescription('Teste');

        $invoice->addItem($item);
        $invoice->removeItem($item);

        self::assertCount(0, $invoice->getItems());
        self::assertNull($item->getInvoice());
    }

    public function testEmailLogHelpers(): void
    {
        $invoice = new Invoice();
        $failed = (new InvoiceEmailLog())
            ->setStatus(InvoiceEmailLog::STATUS_FAILED)
            ->setSentAt(new \DateTimeImmutable('2026-03-15 08:00:00'));
        $success = (new InvoiceEmailLog())
            ->setStatus(InvoiceEmailLog::STATUS_SUCCESS)
            ->setSentAt(new \DateTimeImmutable('2026-03-20 10:00:00'));

        $invoice->addEmailLog($failed)->addEmailLog($success);

        self::assertCount(2, $invoice->getEmailLogs());
        self::assertTrue($invoice->hasSuccessfulEmailDelivery());
        self::assertSame($success->getSentAt(), $invoice->getLastSuccessfulEmailSentAt());
        self::assertSame($invoice, $failed->getInvoice());
    }

    public function testNoSuccessfulEmailDeliveryReturnsNullDate(): void
    {
        $invoice = new Invoice();
        $invoice->addEmailLog((new InvoiceEmailLog())->setStatus(InvoiceEmailLog::STATUS_FAILED));

        self::assertFalse($invoice->hasSuccessfulEmailDelivery());
        self::assertNull($invoice->getLastSuccessfulEmailSentAt());
    }

    public function testValidateDailyRateUniquenessBuildsViolationWhenMoreThanOneDailyRateExists(): void
    {
        $invoice = new Invoice();
        $invoice
            ->addItem((new InvoiceItem())->setBillingType(InvoiceItem::BILLING_DAILY_RATE))
            ->addItem((new InvoiceItem())->setBillingType(InvoiceItem::BILLING_DAILY_RATE));

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder
            ->expects(self::once())
            ->method('atPath')
            ->with('items')
            ->willReturnSelf();
        $violationBuilder
            ->expects(self::once())
            ->method('addViolation');

        /** @var ExecutionContextInterface&MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context
            ->expects(self::once())
            ->method('buildViolation')
            ->with('A invoice pode ter no máximo um item do tipo Daily rate.')
            ->willReturn($violationBuilder);

        $invoice->validateDailyRateUniqueness($context);
    }

    public function testValidateDailyRateUniquenessDoesNotBuildViolationWhenRuleIsRespected(): void
    {
        $invoice = new Invoice();
        $invoice
            ->addItem((new InvoiceItem())->setBillingType(InvoiceItem::BILLING_DAILY_RATE))
            ->addItem((new InvoiceItem())->setBillingType(InvoiceItem::BILLING_ONE_OFF));

        /** @var ExecutionContextInterface&MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context
            ->expects(self::never())
            ->method('buildViolation');

        $invoice->validateDailyRateUniqueness($context);
    }
}
