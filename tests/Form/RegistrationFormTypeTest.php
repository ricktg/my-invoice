<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\RegistrationFormType;

final class RegistrationFormTypeTest extends TypeTestCaseWithValidator
{
    public function testSubmitRegistrationData(): void
    {
        $formData = [
            'email' => 'new.user@example.com',
            'plainPassword' => [
                'first' => 'Abc@1234',
                'second' => 'Abc@1234',
            ],
            'jobDescription' => 'Senior Software Engineer',
            'defaultDailyRate' => '1200.00',
            'defaultHourlyRate' => '135.00',
            'defaultDailyRateCurrency' => 'CAD',
        ];

        $model = new User();
        $form = $this->factory->create(RegistrationFormType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertSame('new.user@example.com', $model->getEmail());
        self::assertSame('Senior Software Engineer', $model->getJobDescription());
        self::assertSame('1200', $model->getDefaultDailyRate());
        self::assertSame('135', $model->getDefaultHourlyRate());
        self::assertSame('CAD', $model->getDefaultDailyRateCurrency());
        self::assertSame('Abc@1234', $form->get('plainPassword')->getData());
    }
}
