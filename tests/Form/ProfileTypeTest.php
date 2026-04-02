<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\ProfileType;

final class ProfileTypeTest extends TypeTestCaseWithValidator
{
    public function testSubmitProfileDataWithoutPasswordChange(): void
    {
        $formData = [
            'jobDescription' => 'Software Consultant',
            'defaultRateValue' => '120.00',
            'defaultRateType' => 'hourly_rate',
            'defaultHourlyHoursPerBusinessDay' => '8.00',
            'defaultDailyRateCurrency' => 'USD',
            'newPassword' => [
                'first' => '',
                'second' => '',
            ],
        ];

        $model = new User();
        $form = $this->factory->create(ProfileType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertSame('Software Consultant', $model->getJobDescription());
        self::assertSame('USD', $model->getDefaultDailyRateCurrency());
        self::assertSame('120', (string) $form->get('defaultRateValue')->getData());
        self::assertSame('hourly_rate', $form->get('defaultRateType')->getData());
        self::assertSame('8', $model->getDefaultHourlyHoursPerBusinessDay());
        self::assertSame('', (string) $form->get('newPassword')->getData());
    }
}
