<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\TwoFactorAuthCustomerApp44;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class Event.
 */
class Event implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            '@admin/Customer/edit.twig' => 'onRenderAdminCustomerEdit',
        ];
    }

    /**
     * [/admin/customer/edit]表示の時のEvent Hook.
     * 二段階認証関連項目を追加する.
     */
    public function onRenderAdminCustomerEdit(TemplateEvent $event): void
    {
        // add twig
        $twig = 'TwoFactorAuthCustomerApp44/Resource/template/admin/customer_edit.twig';
        $event->addSnippet($twig);
    }
}
