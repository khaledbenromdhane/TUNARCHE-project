<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isAdmin = $options['is_admin'];
        $passwordRequired = $options['password_required'];
        $showAvatar = $options['show_avatar'];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr'  => ['placeholder' => 'Votre nom', 'class' => 'form-control']
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr'  => ['placeholder' => 'Votre prénom', 'class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr'  => ['placeholder' => 'votre@email.com', 'class' => 'form-control']
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'attr'  => ['placeholder' => '+216 XX XXX XXX', 'class' => 'form-control']
            ])
            ->add('password', PasswordType::class, [
                'label'    => 'Mot de passe',
                'mapped'   => false,
                'required' => $passwordRequired,
                'attr'     => ['placeholder' => 'Minimum 8 caractères', 'class' => 'form-control'],
                'constraints' => $passwordRequired ? [
                    new Assert\NotBlank(['message' => 'Le mot de passe est obligatoire.']),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'Minimum {{ limit }} caractères.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).+$/',
                        'message' => 'Doit contenir une majuscule, une minuscule et un chiffre.'
                    ])
                ] : []
            ]);

        // ═══════════════════════════════════════════════════════════
        // Upload Avatar (photo de profil)
        // ═══════════════════════════════════════════════════════════
        if ($showAvatar) {
            $builder->add('avatarFile', FileType::class, [
                'label'    => 'Photo de profil',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['class' => 'form-control', 'accept' => 'image/*', 'id' => 'avatarFile'],
                'constraints' => [
                    new Assert\File([
                        'maxSize'   => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Format accepté : JPG, PNG, WebP (max 2MB)'
                    ])
                ]
            ]);
        }

        // ═══════════════════════════════════════════════════════════
        // Choix du rôle
        // ═══════════════════════════════════════════════════════════
        if ($isAdmin) {
            $builder->add('role', ChoiceType::class, [
                'label'    => 'Rôle',
                'mapped'   => false,
                'choices'  => [
                    'Utilisateur'  => 'ROLE_USER',
                    'Artiste'      => 'ROLE_ARTIST',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'expanded' => true,
                'multiple' => false,
                'data'     => $options['default_role'] ?? 'ROLE_USER',
                'attr'     => ['class' => 'form-check']
            ]);
        } else {
            $builder->add('role', ChoiceType::class, [
                'label'    => 'Je suis',
                'mapped'   => false,
                'choices'  => [
                    'Utilisateur' => 'ROLE_USER',
                    'Artiste'     => 'ROLE_ARTIST',
                ],
                'expanded' => true,
                'multiple' => false,
                'data'     => $options['default_role'] ?? 'ROLE_USER',
                'attr'     => ['class' => 'form-check']
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'       => User::class,
            'is_admin'         => false,
            'password_required'=> true,
            'default_role'     => 'ROLE_USER',
            'show_avatar'      => true,
        ]);
    }
}
