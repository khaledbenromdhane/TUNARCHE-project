<?php

namespace App\Form;

use App\Entity\{Evaluation, Formation};
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{IntegerType, TextareaType, TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\{NotBlank, Range};

class EvaluationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('formation', EntityType::class, [
                'class' => Formation::class,
                'choice_label' => 'nomForm',
                'placeholder' => 'Choisir une formation',
            ])
            ->add('titre', TextType::class)
            ->add('note', IntegerType::class, [
                'constraints' => [new Range(['min' => 0, 'max' => 5])],
                'attr' => ['min' => 0, 'max' => 5]
            ])
            ->add('commentaire', TextareaType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evaluation::class,
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}