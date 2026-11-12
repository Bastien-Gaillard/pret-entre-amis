<?php

namespace App\Form;

use App\Entity\Friend;
use App\Entity\Item;
use App\Entity\Loan;
use App\Repository\FriendRepository;
use App\Repository\ItemRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class LoanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('item', EntityType::class, [
                'label' => 'Objet',
                'class' => Item::class,
                'query_builder' => fn (ItemRepository $r) => $r->availableQueryBuilder(),
                'choice_label' => 'name',
                'group_by' => fn (Item $i) => $i->getCategory()->label(),
                'placeholder' => '— Choisir un objet disponible —',
            ])
            ->add('friend', EntityType::class, [
                'label' => 'Prêté à',
                'class' => Friend::class,
                'query_builder' => fn (FriendRepository $r) => $r->orderedByNameQueryBuilder(),
                'choice_label' => 'name',
                'placeholder' => '— Choisir un ami —',
            ])
            ->add('loanedAt', DateType::class, [
                'label' => 'Prêté le',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => ['max' => (new \DateTimeImmutable('today'))->format('Y-m-d')],
            ])
            ->add('dueAt', DateType::class, [
                'label' => 'À rendre le',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'help' => 'Laisse vide si pas de date prévue.',
            ])
            ->add('notes', TextType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['placeholder' => 'Pour quoi faire, état au départ…'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Loan::class]);
    }
}
