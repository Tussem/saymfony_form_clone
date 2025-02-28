<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class LoginFormType extends AbstractType
{
    public function __construct(private CsrfTokenManagerInterface $csrfTokenManager) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('_username', TextType::class, [
                'label' => 'Username',
            ])
            ->add('_password', PasswordType::class, [
                'label' => 'Password',
            ])
            ->add('_csrf_token', HiddenType::class, [
                'mapped' => false,
                'data' => $this->csrfTokenManager->getToken('authenticate')->getValue(),
            ]);
    }
}
