<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\Company;
use App\Form\CompanyType;

final class CompanyTypeTest extends TypeTestCaseWithValidator
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'name' => 'Acme Corp',
            'type' => Company::TYPE_CLIENT,
            'email' => 'finance@acme.com',
            'taxId' => '99-888',
            'countryCode' => 'CA',
            'address' => '123 Main St',
        ];

        $model = new Company();
        $form = $this->factory->create(CompanyType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertSame('Acme Corp', $model->getName());
        self::assertSame(Company::TYPE_CLIENT, $model->getType());
        self::assertSame('finance@acme.com', $model->getEmail());
        self::assertSame('CA', $model->getCountryCode());
    }
}
