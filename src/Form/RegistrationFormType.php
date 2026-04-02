<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'As senhas não conferem.',
                'mapped' => false,
                'first_options' => [
                    'label' => 'Senha',
                    'attr' => [
                        'placeholder' => 'Mínimo 6 caracteres',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmar senha',
                    'attr' => [
                        'placeholder' => 'Repita a senha',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'constraints' => [
                    new NotBlank(message: 'Informe uma senha.'),
                    new Length(min: 6, minMessage: 'A senha deve ter no mínimo {{ limit }} caracteres.'),
                    new Regex(
                        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
                        message: 'Use ao menos 1 minúscula, 1 maiúscula, 1 número e 1 caractere especial.',
                    ),
                ],
            ])
            ->add('jobDescription', TextareaType::class, [
                'label' => 'Descrição do cargo/serviço',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Ex.: Senior Software Engineer - Consulting Services',
                ],
            ])
            ->add('defaultDailyRate', NumberType::class, [
                'label' => 'Valor diário padrão',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'placeholder' => 'Ex.: 850.00',
                ],
            ])
            ->add('defaultHourlyRate', NumberType::class, [
                'label' => 'Valor por hora padrão',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'placeholder' => 'Ex.: 120.00',
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
