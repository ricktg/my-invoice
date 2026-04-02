<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Company;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class CompanyTest extends TestCase
{
    public function testSettersNormalizeValues(): void
    {
        $owner = (new User())->setEmail('owner@example.com');
        $company = (new Company())
            ->setOwner($owner)
            ->setName('  Minha Empresa  ')
            ->setType(Company::TYPE_PROVIDER)
            ->setTaxId(' 12.345.678/0001-90  ')
            ->setCountryCode(' br ')
            ->setAddress("  Rua X, 123  ")
            ->setEmail('  FINANCE@EXAMPLE.COM  ');

        self::assertSame($owner, $company->getOwner());
        self::assertSame('Minha Empresa', $company->getName());
        self::assertSame(Company::TYPE_PROVIDER, $company->getType());
        self::assertSame('12.345.678/0001-90', $company->getTaxId());
        self::assertSame('BR', $company->getCountryCode());
        self::assertSame('Rua X, 123', $company->getAddress());
        self::assertSame('finance@example.com', $company->getEmail());
        self::assertSame('Minha Empresa', (string) $company);
    }

    public function testNullableFieldsCanBeNull(): void
    {
        $company = (new Company())
            ->setTaxId(null)
            ->setAddress(null)
            ->setEmail(null);

        self::assertNull($company->getTaxId());
        self::assertNull($company->getAddress());
        self::assertNull($company->getEmail());
    }

    public function testEmptyEmailBecomesNull(): void
    {
        $company = (new Company())->setEmail('');

        self::assertNull($company->getEmail());
    }
}
