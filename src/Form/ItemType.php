<?php

namespace App\Form;

use App\Entity\Item;
use App\Enum\ItemCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom', 'attr' => ['autofocus' => true, 'placeholder' => 'Perceuse visseuse']])
            ->add('category', EnumType::class, [
                'label' => 'Catégorie',
                'class' => ItemCategory::class,
                'choice_label' => fn (ItemCategory $c) => $c->emoji() . '  ' . $c->label(),
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'État, accessoires fournis…'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Item::class]);
    }
}
