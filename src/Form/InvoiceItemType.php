<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\InvoiceItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('billingType', ChoiceType::class, [
                'label' => 'Tipo de cobrança',
                'choices' => [
                    'Daily rate' => InvoiceItem::BILLING_DAILY_RATE,
                    'Hourly rate' => InvoiceItem::BILLING_HOURLY_RATE,
                    'Cobrança única / reembolso' => InvoiceItem::BILLING_ONE_OFF,
                ],
            ])
            ->add('description', null, ['label' => 'Descrição'])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantidade',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'step' => 1,
                    'min' => 0,
                ],
            ])
            ->add('unitPrice', NumberType::class, [
                'label' => 'Valor unitário',
                'scale' => 2,
                'html5' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceItem::class,
        ]);
    }
}
