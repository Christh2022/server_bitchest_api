<?php

namespace App\Form;

use App\Entity\Admin;
use App\Entity\Clients;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('roles')
            ->add('password')
            ->add('firstName')
            ->add('lastName')
            ->add('address')
            ->add('birthDay', null, [
                'widget' => 'single_text',
            ])
            ->add('created_at', null, [
                'widget' => 'single_text',
            ])
            ->add('updated_at', null, [
                'widget' => 'single_text',
            ])
            ->add('admin_id', EntityType::class, [
                'class' => Admin::class,
                'choice_label' => 'id',
            ])
            ->add('client_id', EntityType::class, [
                'class' => Clients::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
