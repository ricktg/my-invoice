<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Company;
use App\Entity\Invoice;
use App\Entity\User;
use App\Repository\CompanyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class InvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $owner */
        $owner = $options['owner'];

        $builder
            ->add('number', null, ['label' => 'Número da invoice'])
            ->add('issueDate', DateType::class, [
                'label' => 'Data de emissão',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Data de vencimento',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'attr' => [
                    'readonly' => true,
                ],
                'help' => 'Calculado automaticamente: 10 dias após a emissão.',
                'help_html' => true,
            ])
            ->add('issuerCompany', EntityType::class, [
                'label' => 'Empresa emissora (sua PJ)',
                'class' => Company::class,
                'choice_label' => 'name',
                'query_builder' => fn (CompanyRepository $repository) => $repository->createQueryBuilder('c')
                    ->andWhere('c.owner = :owner')
                    ->andWhere('c.type = :type')
                    ->setParameter('owner', $owner)
                    ->setParameter('type', Company::TYPE_PROVIDER)
                    ->orderBy('c.name', 'ASC'),
            ])
            ->add('recipientCompany', EntityType::class, [
                'label' => 'Empresa destinatária',
                'class' => Company::class,
                'choice_label' => 'name',
                'query_builder' => fn (CompanyRepository $repository) => $repository->createQueryBuilder('c')
                    ->andWhere('c.owner = :owner')
                    ->andWhere('c.type = :type')
                    ->setParameter('owner', $owner)
                    ->setParameter('type', Company::TYPE_CLIENT)
                    ->orderBy('c.name', 'ASC'),
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'Moeda',
                'choices' => [
                    'CAD' => 'CAD',
                    'USD' => 'USD',
                    'BRL' => 'BRL',
                    'EUR' => 'EUR',
                    'GBP' => 'GBP',
                ],
            ])
            ->add('language', ChoiceType::class, [
                'label' => 'Idioma da invoice',
                'choices' => [
                    'English' => 'en',
                    'Português (Brasil)' => 'pt-BR',
                ],
            ])
            ->add('referenceMonth', TextType::class, [
                'label' => 'Mês de referência',
                'attr' => [
                    'type' => 'month',
                    'pattern' => '\\d{4}-\\d{2}',
                ],
                'constraints' => [
                    new Regex('/^\d{4}\-\d{2}$/', 'Use o formato YYYY-MM.'),
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Observações',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('items', CollectionType::class, [
                'label' => 'Itens',
                'entry_type' => InvoiceItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
        ]);

        $resolver->setRequired('owner');
        $resolver->setAllowedTypes('owner', User::class);
    }
}
