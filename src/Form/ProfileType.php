<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class ProfileType extends AbstractType
{
    public const RATE_TYPE_DAILY = 'daily_rate';
    public const RATE_TYPE_HOURLY = 'hourly_rate';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jobDescription', TextareaType::class, [
                'label' => 'Descrição do cargo/serviço',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('defaultRateValue', NumberType::class, [
                'label' => 'Valor padrão',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'mapped' => false,
            ])
            ->add('defaultRateType', ChoiceType::class, [
                'label' => 'Tipo do valor padrão',
                'required' => false,
                'mapped' => false,
                'choices' => [
                    'Diário (daily rate)' => self::RATE_TYPE_DAILY,
                    'Por hora (hourly rate)' => self::RATE_TYPE_HOURLY,
                ],
            ])
            ->add('defaultHourlyHoursPerBusinessDay', NumberType::class, [
                'label' => 'Horas por dia útil (Hourly)',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'step' => 0.25,
                    'min' => 0.25,
                    'placeholder' => 'Ex.: 8',
                ],
            ])
            ->add('defaultDailyRateCurrency', ChoiceType::class, [
                'label' => 'Moeda padrão',
                'required' => false,
                'placeholder' => 'Selecione',
                'choices' => [
                    'CAD' => 'CAD',
                    'USD' => 'USD',
                    'BRL' => 'BRL',
                    'EUR' => 'EUR',
                    'GBP' => 'GBP',
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'As senhas não conferem.',
                'mapped' => false,
                'required' => false,
                'first_options' => [
                    'label' => 'Nova senha',
                    'constraints' => [
                        new Length(min: 6, minMessage: 'A senha deve ter no mínimo {{ limit }} caracteres.'),
                    ],
                    'attr' => [
                        'placeholder' => 'Mínimo 6 caracteres',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmar nova senha',
                    'attr' => [
                        'placeholder' => 'Repita a senha',
                        'autocomplete' => 'new-password',
                    ],
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $user = $event->getData();
            if (!$user instanceof User) {
                return;
            }

            if ($user->getDefaultDailyRate() !== null) {
                $event->getForm()->get('defaultRateType')->setData(self::RATE_TYPE_DAILY);
                $event->getForm()->get('defaultRateValue')->setData($user->getDefaultDailyRate());

                return;
            }

            if ($user->getDefaultHourlyRate() !== null) {
                $event->getForm()->get('defaultRateType')->setData(self::RATE_TYPE_HOURLY);
                $event->getForm()->get('defaultRateValue')->setData($user->getDefaultHourlyRate());

                return;
            }

            $event->getForm()->get('defaultRateType')->setData(self::RATE_TYPE_DAILY);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $rateType = (string) $form->get('defaultRateType')->getData();
            $hoursPerBusinessDay = $form->get('defaultHourlyHoursPerBusinessDay')->getData();

            if ($rateType === self::RATE_TYPE_HOURLY && ($hoursPerBusinessDay === null || (float) $hoursPerBusinessDay <= 0)) {
                $form->get('defaultHourlyHoursPerBusinessDay')->addError(
                    new FormError('Informe as horas por dia útil para usar hourly rate.')
                );
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
