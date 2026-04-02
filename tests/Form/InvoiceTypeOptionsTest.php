<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\InvoiceType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InvoiceTypeOptionsTest extends TestCase
{
    public function testOwnerOptionIsRequired(): void
    {
        $type = new InvoiceType();
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $this->expectException(MissingOptionsException::class);
        $resolver->resolve([]);
    }

    public function testOwnerOptionMustBeUserInstance(): void
    {
        $type = new InvoiceType();
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $this->expectException(InvalidOptionsException::class);
        $resolver->resolve(['owner' => 'invalid']);
    }

    public function testOwnerOptionAcceptsUserInstance(): void
    {
        $type = new InvoiceType();
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $resolved = $resolver->resolve(['owner' => new User()]);

        self::assertArrayHasKey('owner', $resolved);
    }
}
