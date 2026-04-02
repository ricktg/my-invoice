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

    public function testPreSetDataUsesDailyDefaultsWhenAvailable(): void
    {
        $model = (new User())
            ->setDefaultDailyRate('900.00')
            ->setDefaultHourlyRate('100.00');

        $form = $this->factory->create(ProfileType::class, $model);

        self::assertSame('daily_rate', $form->get('defaultRateType')->getData());
        self::assertSame('900.00', (string) $form->get('defaultRateValue')->getData());
    }

    public function testHourlyTypeRequiresHoursPerBusinessDay(): void
    {
        $model = new User();
        $form = $this->factory->create(ProfileType::class, $model);

        $form->submit([
            'jobDescription' => 'Consultant',
            'defaultRateValue' => '120.00',
            'defaultRateType' => 'hourly_rate',
            'defaultHourlyHoursPerBusinessDay' => '',
            'defaultDailyRateCurrency' => 'USD',
            'newPassword' => [
                'first' => '',
                'second' => '',
            ],
        ]);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('defaultHourlyHoursPerBusinessDay')->getErrors(true));
    }
}
