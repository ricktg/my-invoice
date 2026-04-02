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
            ->setDefaultDailyRateCurrency(' cad ');

        $user->eraseCredentials();

        self::assertSame('hash123', $user->getPassword());
        self::assertSame('Senior Engineer', $user->getJobDescription());
        self::assertSame('950.00', $user->getDefaultDailyRate());
        self::assertSame('CAD', $user->getDefaultDailyRateCurrency());
    }

    public function testNullableProfileFields(): void
    {
        $user = (new User())
            ->setJobDescription(null)
            ->setDefaultDailyRate(null)
            ->setDefaultDailyRateCurrency(null);

        self::assertNull($user->getJobDescription());
        self::assertNull($user->getDefaultDailyRate());
        self::assertNull($user->getDefaultDailyRateCurrency());
    }
}
