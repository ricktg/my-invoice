<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\InvoiceEmailLog;
use PHPUnit\Framework\TestCase;

final class InvoiceEmailLogTest extends TestCase
{
    public function testConstructorAndMutators(): void
    {
        $log = new InvoiceEmailLog();
        $sentAt = new \DateTimeImmutable('2026-03-10 15:00:00');

        $log
            ->setStatus(InvoiceEmailLog::STATUS_FAILED)
            ->setRecipientEmail('  FINANCE@CLIENT.COM  ')
            ->setSubject('  Invoice INV-001  ')
            ->setErrorMessage('smtp timeout')
            ->setSentAt($sentAt);

        self::assertSame(InvoiceEmailLog::STATUS_FAILED, $log->getStatus());
        self::assertSame('finance@client.com', $log->getRecipientEmail());
        self::assertSame('Invoice INV-001', $log->getSubject());
        self::assertSame('smtp timeout', $log->getErrorMessage());
        self::assertSame($sentAt, $log->getSentAt());
    }
}
