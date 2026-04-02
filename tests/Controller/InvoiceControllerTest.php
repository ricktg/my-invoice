<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Company;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\User;
use App\Service\BusinessDayCalculator;
use Doctrine\ORM\EntityManagerInterface;

final class InvoiceControllerTest extends DatabaseWebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
    }

    public function testNewInvoicePrefillsHourlyQuantityFromBusinessDaysAndProfileHours(): void
    {
        $client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        /** @var BusinessDayCalculator $businessDayCalculator */
        $businessDayCalculator = self::getContainer()->get(BusinessDayCalculator::class);

        [$user, $issuer, $recipient] = $this->createUserAndCompanies();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/invoices/new');

        self::assertResponseIsSuccessful();

        $currentMonth = (new \DateTimeImmutable('today'))->format('Y-m');
        $businessDays = $businessDayCalculator->countWeekdaysInMonth($currentMonth);
        $expectedQuantity = number_format($businessDays * 8.0, 2, '.', '');

        $quantityValue = $crawler->filter('input[name="invoice[items][0][quantity]"]')->attr('value');
        $billingTypeValue = $crawler->filter('select[name="invoice[items][0][billingType]"] option[selected="selected"]')->attr('value');
        $currencyValue = $crawler->filter('select[name="invoice[currency]"] option[selected="selected"]')->attr('value');

        self::assertSame(InvoiceItem::BILLING_HOURLY_RATE, $billingTypeValue);
        self::assertSame($expectedQuantity, $quantityValue);
        self::assertSame('USD', $currencyValue);
    }

    public function testNewInvoiceSubmitRecalculatesHourlyQuantityUsingReferenceMonth(): void
    {
        $client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        /** @var BusinessDayCalculator $businessDayCalculator */
        $businessDayCalculator = self::getContainer()->get(BusinessDayCalculator::class);

        [$user, $issuer, $recipient] = $this->createUserAndCompanies();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/invoices/new');
        self::assertResponseIsSuccessful();

        $referenceMonth = '2026-02';
        $businessDays = $businessDayCalculator->countWeekdaysInMonth($referenceMonth);
        $expectedQuantity = number_format($businessDays * 8.0, 2, '.', '');

        $form = $crawler->selectButton('Salvar invoice')->form([
            'invoice[number]' => 'INV-HOURLY-001',
            'invoice[issueDate]' => '2026-02-10',
            'invoice[dueDate]' => '2026-02-20',
            'invoice[issuerCompany]' => (string) $issuer->getId(),
            'invoice[recipientCompany]' => (string) $recipient->getId(),
            'invoice[currency]' => 'USD',
            'invoice[referenceMonth]' => $referenceMonth,
            'invoice[notes]' => 'Teste hourly',
            'invoice[items][0][billingType]' => InvoiceItem::BILLING_HOURLY_RATE,
            'invoice[items][0][description]' => 'Consulting',
            'invoice[items][0][quantity]' => '1',
            'invoice[items][0][unitPrice]' => '120',
        ]);
        $client->submit($form);

        self::assertResponseRedirects();

        $invoice = $this->entityManager->getRepository(Invoice::class)->findOneBy(['number' => 'INV-HOURLY-001']);
        self::assertInstanceOf(Invoice::class, $invoice);
        self::assertCount(1, $invoice->getItems());
        self::assertEquals((float) $expectedQuantity, (float) $invoice->getItems()->first()->getQuantity());
    }

    public function testEditInvoiceSubmitRecalculatesHourlyQuantityUsingReferenceMonth(): void
    {
        $client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        /** @var BusinessDayCalculator $businessDayCalculator */
        $businessDayCalculator = self::getContainer()->get(BusinessDayCalculator::class);

        [$user, $issuer, $recipient] = $this->createUserAndCompanies();

        $invoice = (new Invoice())
            ->setOwner($user)
            ->setNumber('INV-HOURLY-EDIT')
            ->setIssueDate(new \DateTimeImmutable('2026-03-05'))
            ->setDueDate(new \DateTimeImmutable('2026-03-15'))
            ->setIssuerCompany($issuer)
            ->setRecipientCompany($recipient)
            ->setCurrency('USD')
            ->setReferenceMonth('2026-03');

        $invoice->addItem(
            (new InvoiceItem())
                ->setBillingType(InvoiceItem::BILLING_HOURLY_RATE)
                ->setDescription('Initial')
                ->setQuantity('5')
                ->setUnitPrice('120')
        );

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $client->loginUser($user);
        $crawler = $client->request('GET', sprintf('/invoices/%d/edit', $invoice->getId()));
        self::assertResponseIsSuccessful();

        $referenceMonth = '2026-01';
        $businessDays = $businessDayCalculator->countWeekdaysInMonth($referenceMonth);
        $expectedQuantity = number_format($businessDays * 8.0, 2, '.', '');

        $form = $crawler->selectButton('Salvar invoice')->form([
            'invoice[number]' => 'INV-HOURLY-EDIT',
            'invoice[issueDate]' => '2026-01-10',
            'invoice[dueDate]' => '2026-01-20',
            'invoice[issuerCompany]' => (string) $issuer->getId(),
            'invoice[recipientCompany]' => (string) $recipient->getId(),
            'invoice[currency]' => 'USD',
            'invoice[referenceMonth]' => $referenceMonth,
            'invoice[notes]' => '',
            'invoice[items][0][billingType]' => InvoiceItem::BILLING_HOURLY_RATE,
            'invoice[items][0][description]' => 'Edited',
            'invoice[items][0][quantity]' => '2',
            'invoice[items][0][unitPrice]' => '120',
        ]);
        $client->submit($form);

        self::assertResponseRedirects();
        $this->entityManager->clear();

        $updated = $this->entityManager->getRepository(Invoice::class)->find($invoice->getId());
        self::assertInstanceOf(Invoice::class, $updated);
        self::assertSame($referenceMonth, $updated->getReferenceMonth());
        self::assertEquals((float) $expectedQuantity, (float) $updated->getItems()->first()->getQuantity());
    }

    /**
     * @return array{0: User, 1: Company, 2: Company}
     */
    private function createUserAndCompanies(): array
    {
        $user = (new User())
            ->setEmail('invoice-user@example.com')
            ->setPassword('hash')
            ->setRoles(['ROLE_USER'])
            ->setJobDescription('Software Consulting')
            ->setDefaultHourlyRate('120.00')
            ->setDefaultHourlyHoursPerBusinessDay('8.00')
            ->setDefaultDailyRateCurrency('USD');

        $issuer = (new Company())
            ->setOwner($user)
            ->setType(Company::TYPE_PROVIDER)
            ->setName('Issuer LLC')
            ->setCountryCode('US');

        $recipient = (new Company())
            ->setOwner($user)
            ->setType(Company::TYPE_CLIENT)
            ->setName('Recipient Inc')
            ->setCountryCode('CA');

        $this->entityManager->persist($user);
        $this->entityManager->persist($issuer);
        $this->entityManager->persist($recipient);
        $this->entityManager->flush();

        return [$user, $issuer, $recipient];
    }
}
