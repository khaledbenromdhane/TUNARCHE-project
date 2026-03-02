<?php

namespace App\Form;

use App\Entity\Galerie;
use App\Entity\Oeuvre;
use App\Entity\User;
use App\Repository\GalerieRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OeuvreType extends AbstractType
{
    public function __construct(
        private UserRepository $userRepository,
        private GalerieRepository $galerieRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('galerie', EntityType::class, [
                'class' => Galerie::class,
                'choice_label' => 'nom',
                'query_builder' => fn (GalerieRepository $gr) => $gr->createQueryBuilder('g')
                    ->orderBy('g.nom', 'ASC'),
                'label' => 'Galerie',
                'placeholder' => 'Sélectionner une galerie',
                'required' => false, // Validation via Assert\NotNull sur l'entité
            ])
            ->add('artiste', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $u) => $u->getFullName(),
                'query_builder' => fn (UserRepository $ur) => $ur->createQueryBuilder('u')
                    ->where("u.role LIKE :role")
                    ->setParameter('role', '%ROLE_ARTIST%')
                    ->orderBy('u.nom', 'ASC'),
                'label' => 'Artiste auteur',
                'placeholder' => 'Sélectionner un artiste',
                'required' => false, // Validation via Assert\NotNull sur l'entité
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'œuvre',
                'attr' => ['placeholder' => 'Ex: La Nuit Étoilée'],
                'required' => false, // Validation via Assert (NotBlank, Length) sur l'entité
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (DT)',
                'scale' => 2,
                'attr' => ['placeholder' => '0.00'],
                'required' => false, // Validation via Assert (NotBlank, PositiveOrZero) sur l'entité
            ])
            ->add('etat', ChoiceType::class, [
                'label' => 'État',
                'choices' => [
                    'Neuve' => Oeuvre::ETAT_NEUVE,
                    'Défectueuse' => Oeuvre::ETAT_DEFECTUEUSE,
                ],
                'required' => false,
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Disponible' => Oeuvre::STATUT_DISPONIBLE,
                    'Vendue' => Oeuvre::STATUT_VENDUE,
                ],
                'required' => true,
            ])
            ->add('anneeRealisation', IntegerType::class, [
                'label' => 'Année de réalisation',
                'attr' => ['placeholder' => 'Ex: 2024'],
                'required' => false, // Validation via Assert (NotNull, Range 1000-2100) sur l'entité
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => 'image/*'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['placeholder' => 'Description détaillée de l\'œuvre...', 'rows' => 5],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Oeuvre::class,
        ]);
    }
}
