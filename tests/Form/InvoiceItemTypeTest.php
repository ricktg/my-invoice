<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\InvoiceItem;
use App\Form\InvoiceItemType;

final class InvoiceItemTypeTest extends TypeTestCaseWithValidator
{
    public function testSubmitDailyRateItem(): void
    {
        $formData = [
            'billingType' => InvoiceItem::BILLING_DAILY_RATE,
            'description' => 'Consulting',
            'quantity' => '22',
            'unitPrice' => '900.00',
        ];

        $model = new InvoiceItem();
        $form = $this->factory->create(InvoiceItemType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertSame(InvoiceItem::BILLING_DAILY_RATE, $model->getBillingType());
        self::assertSame('Consulting', $model->getDescription());
        self::assertNotSame('', $model->getQuantity());
        self::assertNotSame('', $model->getUnitPrice());
        self::assertSame('19800.00', $model->getTotalAmount());
    }

    public function testSubmitHourlyRateItemWithDecimalHours(): void
    {
        $formData = [
            'billingType' => InvoiceItem::BILLING_HOURLY_RATE,
            'description' => 'Development work',
            'quantity' => '7.5',
            'unitPrice' => '120.00',
        ];

        $model = new InvoiceItem();
        $form = $this->factory->create(InvoiceItemType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertSame(InvoiceItem::BILLING_HOURLY_RATE, $model->getBillingType());
        self::assertSame('Development work', $model->getDescription());
        self::assertSame('7.5', $model->getDisplayQuantity());
        self::assertSame('900.00', $model->getTotalAmount());
    }
}
