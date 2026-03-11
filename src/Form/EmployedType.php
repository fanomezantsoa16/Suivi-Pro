<?php

// src/Form/EmployedType.php

namespace App\Form;

use App\Entity\Employed;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmployedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fname')
            ->add('lname')
            ->add('email')
            ->add('phone')
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Développeur' => 'Développeur',
                    'Designer' => 'Designer',
                    'Manager' => 'Manager',
                    'Commercial' => 'Commercial',
                    'Support' => 'Support',
                    'Autre' => 'Autre',
                ],
                'placeholder' => 'Sélectionner un rôle',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employed::class,
        ]);
    }
}
