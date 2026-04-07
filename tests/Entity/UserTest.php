<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testEmailAndIdentifierNormalization(): void
    {
        $user = (new User())->setEmail('  USER@EXAMPLE.COM  ');

        self::assertSame('user@example.com', $user->getEmail());
        self::assertSame('user@example.com', $user->getUserIdentifier());
    }

    public function testRolesAlwaysContainRoleUserWithoutDuplicates(): void
    {
        $user = (new User())->setRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ADMIN']);

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testPasswordAndProfileFields(): void
    {
        $user = (new User())
            ->setPassword('hash123')
            ->setJobDescription('  Senior Engineer  ')
            ->setDefaultDailyRate('950.00')
            ->setDefaultHourlyRate('120.00')
            ->setDefaultAnnualFixedRate('25000.00')
            ->setDefaultHourlyHoursPerBusinessDay('7.50')
            ->setDefaultDailyRateCurrency(' cad ')
            ->setLocalCurrency(' brl ')
            ->setDefaultInvoiceLanguage(' pt-BR ');

        $user->eraseCredentials();

        self::assertSame('hash123', $user->getPassword());
        self::assertSame('Senior Engineer', $user->getJobDescription());
        self::assertSame('950.00', $user->getDefaultDailyRate());
        self::assertSame('120.00', $user->getDefaultHourlyRate());
        self::assertSame('25000.00', $user->getDefaultAnnualFixedRate());
        self::assertSame('7.50', $user->getDefaultHourlyHoursPerBusinessDay());
        self::assertSame('CAD', $user->getDefaultDailyRateCurrency());
        self::assertSame('BRL', $user->getLocalCurrency());
        self::assertSame('pt-BR', $user->getDefaultInvoiceLanguage());
    }

    public function testNullableProfileFields(): void
    {
        $user = (new User())
            ->setJobDescription(null)
            ->setDefaultDailyRate(null)
            ->setDefaultHourlyRate(null)
            ->setDefaultAnnualFixedRate(null)
            ->setDefaultHourlyHoursPerBusinessDay(null)
            ->setDefaultDailyRateCurrency(null)
            ->setLocalCurrency(null)
            ->setDefaultInvoiceLanguage(null);

        self::assertNull($user->getJobDescription());
        self::assertNull($user->getDefaultDailyRate());
        self::assertNull($user->getDefaultHourlyRate());
        self::assertNull($user->getDefaultAnnualFixedRate());
        self::assertNull($user->getDefaultHourlyHoursPerBusinessDay());
        self::assertNull($user->getDefaultDailyRateCurrency());
        self::assertNull($user->getLocalCurrency());
        self::assertNull($user->getDefaultInvoiceLanguage());
    }
}
