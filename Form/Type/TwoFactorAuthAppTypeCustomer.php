<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * https://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\TwoFactorAuthCustomerApp44\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class TwoFactorAuthAppTypeCustomer extends AbstractType
{
    /**
     * buildForm.
     *
     * @param FormBuilderInterface $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'one_time_token', TextType::class, [
                    'label' => 'front.setting.system.two_factor_auth.device_token',
                    'required' => true,
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(max: 6, min: 6),
                    ],
                    'attr' => [
                        'maxlength' => 6,
                        'style' => 'width: 100px;',
                    ],
                ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'plg_customer_2fa';
    }
}
