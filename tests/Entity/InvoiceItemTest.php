<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\InvoiceItem;
use PHPUnit\Framework\TestCase;

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
    }
}
