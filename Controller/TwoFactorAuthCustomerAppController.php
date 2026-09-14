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

namespace Plugin\TwoFactorAuthCustomerApp44\Controller;

use Eccube\Entity\Customer;
use Plugin\TwoFactorAuthCustomer44\Controller\TwoFactorAuthCustomerController;
use Plugin\TwoFactorAuthCustomerApp44\Form\Type\TwoFactorAuthAppTypeCustomer;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\TwoFactorAuthException;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class TwoFactorAuthCustomerAppController extends TwoFactorAuthCustomerController
{
    /**
     * @var string 設定用認証キーを保存するセッションキー名
     */
    protected const SESSION_APP_AUTH_KEY = 'plugin_eccube_customer_2fa_app_auth_key';

    /**
     * 初回APP認証画面.
     *
     * @return array<string, mixed>|RedirectResponse
     */
    #[Route(path: '/mypage/two_factor_auth/app/create', name: 'plg_customer_2fa_app_create', methods: ['GET', 'POST'])]
    #[Template('@TwoFactorAuthCustomerApp44/default/tfa/app/register.twig')]
    public function create(Request $request)
    {
        if ($this->isTwoFactorAuthed()) {
            return $this->redirectToRoute($this->getCallbackRoute());
        }

        $tfa = new TwoFactorAuth();

        $error = null;
        $Customer = $this->getUser();
        if (!$Customer instanceof Customer) {
            return $this->redirectToRoute('mypage');
        }

        $builder = $this->formFactory->createBuilder(TwoFactorAuthAppTypeCustomer::class);
        $form = $builder->getForm();
        $auth_key = null;

        if ('GET' === $request->getMethod()) {
            if ($Customer->getTwoFactorAuthSecret()) {
                // 既に二段階認証設定済み + APP認証設定済み(二回目以降)
                return [
                    'form' => $form->createView(),
                    'Customer' => $Customer,
                ];
            }
            $auth_key = $this->createSecret($tfa);
            $this->session->set(self::SESSION_APP_AUTH_KEY, $auth_key);
        } elseif ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            $auth_key = $this->session->get(self::SESSION_APP_AUTH_KEY);
            if ($form->isSubmitted() && $form->isValid()) {
                $token = $form->get('one_time_token')->getData();
                if (is_string($auth_key) && is_string($token) && $this->verifyCode($tfa, $auth_key, $token)) {
                    // 秘密鍵更新
                    $Customer->setTwoFactorAuthSecret($auth_key);
                    $this->entityManager->persist($Customer);
                    $this->entityManager->flush();
                    $this->addSuccess('front.2fa.complete_message');
                    $this->session->remove(self::SESSION_APP_AUTH_KEY);

                    $response = $this->redirectToRoute($this->getCallbackRoute());
                    $response->headers->setCookie(
                        $this->customerTwoFactorAuthService->createAuthedCookie(
                            $Customer,
                            $this->getCallbackRoute()
                        )
                    );

                    return $response;
                }
                $error = trans('front.2fa.onetime.invalid_message__reinput');
            } else {
                $error = trans('front.2fa.onetime.invalid_message__reinput');
            }
        }

        return [
            'form' => $form->createView(),
            'Customer' => $Customer,
            'auth_key' => $auth_key,
            'error' => $error,
        ];
    }

    /**
     * APP認証画面.
     *
     * @return array<string, mixed>|RedirectResponse
     */
    #[Route(path: '/mypage/two_factor_auth/app/challenge', name: 'plg_customer_2fa_app_challenge', methods: ['GET', 'POST'])]
    #[Template('@TwoFactorAuthCustomerApp44/default/tfa/app/challenge.twig')]
    public function challenge(Request $request)
    {
        if ($this->isTwoFactorAuthed()) {
            return $this->redirectToRoute($this->getCallbackRoute());
        }

        $tfa = new TwoFactorAuth();

        $error = null;
        $Customer = $this->getUser();
        if (!$Customer instanceof Customer) {
            return $this->redirectToRoute('mypage');
        }

        if ($Customer->getTwoFactorAuthSecret() === null) {
            // APP認証設定まだ
            return $this->redirectToRoute('plg_customer_2fa_app_create');
        }

        $builder = $this->formFactory->createBuilder(TwoFactorAuthAppTypeCustomer::class);
        $builder->remove('auth_key');
        $form = $builder->getForm();

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $secret = $Customer->getTwoFactorAuthSecret();
                $token = $form->get('one_time_token')->getData();
                if (is_string($secret) && is_string($token) && $this->verifyCode($tfa, $secret, $token)) {
                    $response = $this->redirectToRoute($this->getCallbackRoute());
                    $response->headers->setCookie(
                        $this->customerTwoFactorAuthService->createAuthedCookie(
                            $Customer,
                            $this->getCallbackRoute()
                        )
                    );

                    return $response;
                }
                $error = trans('front.2fa.onetime.invalid_message__reinput');
            } else {
                $error = trans('front.2fa.onetime.invalid_message__reinput');
            }
        }

        return [
            'form' => $form->createView(),
            'error' => $error,
        ];
    }

    /**
     * 秘密鍵生成.
     *
     * @param TwoFactorAuth $tfa
     *
     * @throws TwoFactorAuthException
     */
    private function createSecret(TwoFactorAuth $tfa): string
    {
        return $tfa->createSecret();
    }

    /**
     * 認証コードを検証.
     *
     * @param TwoFactorAuth $tfa
     * @param string $authKey
     * @param string $token
     */
    private function verifyCode(TwoFactorAuth $tfa, string $authKey, string $token): bool
    {
        return $tfa->verifyCode($authKey, $token, 1);
    }
}
