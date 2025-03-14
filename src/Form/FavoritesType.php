<?php
namespace App\Form;

use App\Entity\Favorites;
use App\Entity\Film;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FavoritesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Set the user automatically to the logged-in user
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username', // Display the username instead of the ID
                'disabled' => true, // Disable the field, as the user is already set automatically
            ])
            ->add('film', EntityType::class, [
                'class' => Film::class,
                'choice_label' => 'title', // Display the film's title instead of the ID
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Favorites::class,
        ]);
    }
}
