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

    public function testNewInvoicePrefillsLanguageFromProfileDefault(): void
    {
        $client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        [$user] = $this->createUserAndCompanies();
        $user->setDefaultInvoiceLanguage('pt-BR');
        $this->entityManager->flush();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/invoices/new');
        self::assertResponseIsSuccessful();

        $languageValue = $crawler->filter('select[name="invoice[language]"] option[selected="selected"]')->attr('value');
        self::assertSame('pt-BR', $languageValue);
    }

    public function testNewInvoiceSubmitKeepsManuallyTypedHourlyQuantity(): void
    {
        $client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        [$user, $issuer, $recipient] = $this->createUserAndCompanies();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/invoices/new');
        self::assertResponseIsSuccessful();

        $referenceMonth = '2026-02';

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
        self::assertEquals(1.0, (float) $invoice->getItems()->first()->getQuantity());
    }

    public function testEditInvoiceSubmitKeepsManuallyTypedQuantity(): void
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
        self::assertEquals(2.0, (float) $updated->getItems()->first()->getQuantity());
    }

    public function testEditDailyRateKeepsManuallyTypedQuantity(): void
    {
        $client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        [$user, $issuer, $recipient] = $this->createUserAndCompanies();

        $invoice = (new Invoice())
            ->setOwner($user)
            ->setNumber('INV-DAILY-EDIT')
            ->setIssueDate(new \DateTimeImmutable('2026-03-05'))
            ->setDueDate(new \DateTimeImmutable('2026-03-15'))
            ->setIssuerCompany($issuer)
            ->setRecipientCompany($recipient)
            ->setCurrency('USD')
            ->setReferenceMonth('2026-03');

        $invoice->addItem(
            (new InvoiceItem())
                ->setBillingType(InvoiceItem::BILLING_DAILY_RATE)
                ->setDescription('Daily service')
                ->setQuantity('22')
                ->setUnitPrice('850')
        );

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $client->loginUser($user);
        $crawler = $client->request('GET', sprintf('/invoices/%d/edit', $invoice->getId()));
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Salvar invoice')->form([
            'invoice[number]' => 'INV-DAILY-EDIT',
            'invoice[issueDate]' => '2026-03-10',
            'invoice[dueDate]' => '2026-03-20',
            'invoice[issuerCompany]' => (string) $issuer->getId(),
            'invoice[recipientCompany]' => (string) $recipient->getId(),
            'invoice[currency]' => 'USD',
            'invoice[referenceMonth]' => '2026-03',
            'invoice[notes]' => '',
            'invoice[items][0][billingType]' => InvoiceItem::BILLING_DAILY_RATE,
            'invoice[items][0][description]' => 'Daily service',
            'invoice[items][0][quantity]' => '11',
            'invoice[items][0][unitPrice]' => '850',
        ]);
        $client->submit($form);

        self::assertResponseRedirects();
        $this->entityManager->clear();

        $updated = $this->entityManager->getRepository(Invoice::class)->find($invoice->getId());
        self::assertInstanceOf(Invoice::class, $updated);
        self::assertEquals(11.0, (float) $updated->getItems()->first()->getQuantity());
    }

    public function testNewInvoicePrefillsAnnualFixedAsMonthlyOneOffItem(): void
    {
        $client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        [$user, $issuer, $recipient] = $this->createUserAndCompanies();
        $user
            ->setDefaultHourlyRate(null)
            ->setDefaultHourlyHoursPerBusinessDay(null)
            ->setDefaultDailyRate(null)
            ->setDefaultAnnualFixedRate('24000.00');
        $this->entityManager->flush();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/invoices/new');

        self::assertResponseIsSuccessful();

        $quantityValue = $crawler->filter('input[name="invoice[items][0][quantity]"]')->attr('value');
        $unitPriceValue = $crawler->filter('input[name="invoice[items][0][unitPrice]"]')->attr('value');
        $billingTypeValue = $crawler->filter('select[name="invoice[items][0][billingType]"] option[selected="selected"]')->attr('value');

        self::assertSame(InvoiceItem::BILLING_ONE_OFF, $billingTypeValue);
        self::assertSame('1.00', $quantityValue);
        self::assertSame('2000.00', $unitPriceValue);
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
