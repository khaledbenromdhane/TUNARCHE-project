<?php
namespace App\Form;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{EmailType, FileType, PasswordType, RepeatedType, TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label'=>'Nom','attr'=>['class'=>'form-control']])
            ->add('prenom', TextType::class, ['label'=>'Prénom','attr'=>['class'=>'form-control']])
            ->add('email', EmailType::class, ['label'=>'Email','attr'=>['class'=>'form-control']])
            ->add('telephone', TextType::class, ['label'=>'Téléphone','attr'=>['class'=>'form-control']])
            ->add('avatarFile', FileType::class, ['label'=>'Photo','mapped'=>false,'required'=>false,'attr'=>['class'=>'form-control','accept'=>'image/*','id'=>'avatarFile'],'constraints'=>[new Assert\File(['maxSize'=>'2M','mimeTypes'=>['image/jpeg','image/png','image/webp']])]])
            ->add('newPassword', RepeatedType::class, ['type'=>PasswordType::class,'mapped'=>false,'required'=>false,'first_options'=>['label'=>'Nouveau mot de passe','attr'=>['class'=>'form-control','placeholder'=>'Laisser vide pour ne pas changer']],'second_options'=>['label'=>'Confirmer','attr'=>['class'=>'form-control']],'invalid_message'=>'Les mots de passe ne correspondent pas.','constraints'=>[new Assert\Length(['min'=>8,'minMessage'=>'Min. 8 caractères.'])]]);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class'=>User::class]); }
}
