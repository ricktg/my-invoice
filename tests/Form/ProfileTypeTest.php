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
            'defaultDailyRate' => '850.00',
            'defaultDailyRateCurrency' => 'USD',
            'newPassword' => '',
        ];

        $model = new User();
        $form = $this->factory->create(ProfileType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertSame('Software Consultant', $model->getJobDescription());
        self::assertSame('850', $model->getDefaultDailyRate());
        self::assertSame('USD', $model->getDefaultDailyRateCurrency());
        self::assertNull($form->get('newPassword')->getData());
    }
}
