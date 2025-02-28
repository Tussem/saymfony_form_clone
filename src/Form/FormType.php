<?php

namespace App\Form;

use App\Entity\Form;
use App\Entity\User;
use App\Model\FormData;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Add static fields for the Form entity
        $builder
            ->add('title')
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ]);

        // Add dynamic fields using FormData (not part of the Form entity)
        $formEntity = $options['data'];

        if ($formEntity && $formEntity->getFields()) {
            foreach ($formEntity->getFields() as $field) {
                $fieldName = 'field_' . $field->getId();
                // Add dynamic fields (select, checkbox, etc.)
                $builder->add($fieldName, ChoiceType::class, [
                    'choices' => $this->getChoicesFromOptions($field->getOptions()),  // Convert options to choices
                    'expanded' => $field->getType() === 'checkbox',  // Handle checkboxes
                    'multiple' => $field->getType() === 'checkbox',  // Multiple for checkboxes
                ]);
            }
        }
    }

    private function getChoicesFromOptions($options)
    {
        // Convert comma-separated options into an array for choices
        return array_flip(explode(',', $options));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Form::class,  // Link the form to the Form entity
            'form_data_class' => FormData::class,  // Specify the FormData class for dynamic fields
        ]);
    }
}
