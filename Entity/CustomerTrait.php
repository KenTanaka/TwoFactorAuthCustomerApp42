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

namespace Plugin\TwoFactorAuthCustomerApp44\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Attribute\EntityExtension;
use Eccube\Entity\Customer;

#[EntityExtension(Customer::class)]
trait CustomerTrait
{
    #[ORM\Column(name: 'two_factor_auth_secret', type: Types::STRING, length: 255, nullable: true)]
    private ?string $two_factor_auth_secret = null;

    public function getTwoFactorAuthSecret(): ?string
    {
        return $this->two_factor_auth_secret;
    }

    public function setTwoFactorAuthSecret(?string $two_factor_auth_secret): void
    {
        $this->two_factor_auth_secret = $two_factor_auth_secret;
    }
}
