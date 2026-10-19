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

namespace Plugin\TwoFactorAuthCustomerApp44\Tests\Web;

use Eccube\Entity\Customer;
use Eccube\Tests\Web\AbstractWebTestCase;
use RobThree\Auth\Providers\Qr\QRServerProvider;
use RobThree\Auth\TwoFactorAuth;
use Symfony\Component\DomCrawler\Crawler;

class TwoFactorAuthCustomerAppControllerTest extends AbstractWebTestCase
{
    private const SESSION_APP_AUTH_KEY = 'plugin_eccube_customer_2fa_app_auth_key';

    private ?Customer $Customer = null;

    protected function setUp(): void
    {
        parent::setUp();

        // 登録画面の POST には IP・会員単位のレート制限が掛かり、その記録はロールバックされないため消しておく
        static::getContainer()->get('rate_limiter.cache')->clear();

        $this->Customer = $this->createCustomer();
        $this->loginTo($this->Customer);
    }

    public function testCreateRegistersSecretWhenCodeIsValid(): void
    {
        $this->client->request('GET', $this->generateUrl('plg_customer_2fa_app_create'));
        $authKey = $this->getSessionAuthKey();
        self::assertIsString($authKey);

        $code = (new TwoFactorAuth(new QRServerProvider()))->getCode($authKey);
        $this->postCreate($code);

        self::assertTrue($this->client->getResponse()->isRedirect());
        self::assertSame($authKey, $this->findCustomer()->getTwoFactorAuthSecret());
    }

    public function testCreateKeepsSecretWhenCodeIsInvalid(): void
    {
        $this->client->request('GET', $this->generateUrl('plg_customer_2fa_app_create'));
        $authKey = $this->getSessionAuthKey();

        $crawler = $this->postCreate('000000');

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertSame($authKey, $this->getSessionAuthKey());
        self::assertSame(
            trim(static::getContainer()->get('translator')->trans('front.2fa.onetime.invalid_message__reinput')),
            trim($crawler->filter('.ec-errorMessage')->text())
        );
        self::assertNull($this->findCustomer()->getTwoFactorAuthSecret());
    }

    public function testCreateReissuesSecretWhenSessionKeyIsMissing(): void
    {
        // GET で鍵を発行せずに POST し、セッションに鍵が無い状態を再現する
        $crawler = $this->postCreate('123456');

        self::assertTrue($this->client->getResponse()->isOk());

        $authKey = $this->getSessionAuthKey();
        self::assertIsString($authKey);
        self::assertNotSame('', $authKey);
        self::assertSame($authKey, $crawler->filter('.app_auth_key p')->text());

        $message = static::getContainer()->get('translator')->trans('front.2fa.app.secret_regenerated');
        self::assertStringContainsString(strtok($message, "\n"), $crawler->filter('.ec-errorMessage')->text());
        self::assertNull($this->findCustomer()->getTwoFactorAuthSecret());
    }

    public function testCreateRedirectsToChallengeWhenSecretIsAlreadyRegistered(): void
    {
        $this->Customer->setTwoFactorAuthSecret('REGISTEREDSECRET');
        $this->entityManager->flush();

        $this->postCreate('123456');

        self::assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plg_customer_2fa_app_challenge')));
        self::assertNull($this->getSessionAuthKey());
        self::assertSame('REGISTEREDSECRET', $this->findCustomer()->getTwoFactorAuthSecret());
    }

    private function postCreate(string $code): Crawler
    {
        return $this->client->request('POST', $this->generateUrl('plg_customer_2fa_app_create'), [
            'plg_customer_2fa' => [
                'one_time_token' => $code,
                '_token' => 'dummy',
            ],
        ]);
    }

    private function getSessionAuthKey(): mixed
    {
        return $this->client->getRequest()->getSession()->get(self::SESSION_APP_AUTH_KEY);
    }

    private function findCustomer(): Customer
    {
        $this->entityManager->clear();
        $Customer = $this->entityManager->find(Customer::class, $this->Customer->getId());
        self::assertInstanceOf(Customer::class, $Customer);

        return $Customer;
    }
}
