<?php

namespace App\Form;

use App\Entity\Formation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, DateType, TextareaType, TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\{NotBlank, Length};

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomForm', TextType::class, [
                'label' => 'Nom de la formation',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                    new Length(['min' => 3, 'minMessage' => 'Minimum {{ limit }} caractères']),
                ]
            ])
            ->add('dateForm', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices'  => [
                    'Technique' => 'Technique',
                    'Management' => 'Management',
                    'Soft Skills' => 'Soft Skills',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 4]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}