<?php

namespace App\Form;

use App\Entity\Galerie;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GalerieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categorie', TextType::class, [
                'label' => 'Catégorie',
                'attr' => ['placeholder' => 'Ex: Moderne, Classique, Contemporain'],
                'required' => false,
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Ex: Galerie Moderne'],
                'required' => false,
            ])
            ->add('nbOeuvresDispo', IntegerType::class, [
                'label' => 'Nombre d\'œuvres disponibles',
                'attr' => ['placeholder' => '0'],
                'required' => false,
            ])
            ->add('artistes', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $u) => $u->getNomComplet(),
                'query_builder' => fn (UserRepository $ur) => $ur->createQueryBuilder('u')
                    ->andWhere('u.role = :role')
                    ->setParameter('role', 'artist')
                    ->orderBy('u.nomuser', 'ASC'),
                'multiple' => true,
                'expanded' => false,
                'label' => 'Artistes',
                'placeholder' => 'Sélectionner un ou plusieurs artistes',
                'attr' => ['class' => 'form-select', 'title' => 'Maintenir Ctrl (ou Cmd) pour sélectionner plusieurs artistes'],
                'required' => false,
            ])
            ->add('nbEmployes', IntegerType::class, [
                'label' => 'Nombre d\'employés',
                'attr' => ['placeholder' => '0'],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Galerie::class,
        ]);
    }
}
