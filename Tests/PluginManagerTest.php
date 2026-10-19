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

namespace Plugin\TwoFactorAuthCustomerApp44\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Tests\EccubeTestCase;
use Plugin\TwoFactorAuthCustomerApp44\PluginManager;

class PluginManagerTest extends EccubeTestCase
{
    private const PAGE_URLS = ['plg_customer_2fa_app_create', 'plg_customer_2fa_app_challenge'];

    public function testRemovePagesDeletesRegisteredPagesAndLayouts(): void
    {
        // disable() / uninstall() はテンプレートの削除も行うため、ページの登録・削除だけを呼び出す
        $pluginManager = new class extends PluginManager {
            public function callCreatePages(EntityManagerInterface $em): void
            {
                $this->createPages($em);
            }

            public function callRemovePages(EntityManagerInterface $em): void
            {
                $this->removePages($em);
            }
        };

        $pluginManager->callCreatePages($this->entityManager);

        $pageIds = array_map(
            static fn (Page $Page): ?int => $Page->getId(),
            $this->entityManager->getRepository(Page::class)->findBy(['url' => self::PAGE_URLS])
        );
        self::assertCount(2, $pageIds);
        self::assertSame(2, $this->entityManager->getRepository(PageLayout::class)->count(['page_id' => $pageIds]));

        $pluginManager->callRemovePages($this->entityManager);

        self::assertSame(0, $this->entityManager->getRepository(Page::class)->count(['url' => self::PAGE_URLS]));
        self::assertSame(0, $this->entityManager->getRepository(PageLayout::class)->count(['page_id' => $pageIds]));
    }
}
