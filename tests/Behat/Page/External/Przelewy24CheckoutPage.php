<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace Tests\BitBag\SyliusPrzelewy24Plugin\Behat\Page\External;

use Behat\Mink\Session;
use BitBag\SyliusPrzelewy24Plugin\Bridge\Przelewy24BridgeInterface;
use Payum\Core\Security\TokenInterface;
use Sylius\Behat\Page\Page;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\BrowserKit\Client;
use Tests\BitBag\SyliusPrzelewy24Plugin\Behat\Service\Mocker\Przelewy24ApiMocker;

final class Przelewy24CheckoutPage extends Page implements Przelewy24CheckoutPageInterface
{
    /**
     * @var Przelewy24ApiMocker
     */
    private $przelewy24ApiMocker;

    /**
     * @var RepositoryInterface
     */
    private $securityTokenRepository;

    /**
     * @var EntityRepository
     */
    private $paymentRepository;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param Session $session
     * @param array $parameters
     * @param Przelewy24ApiMocker $przelewy24ApiMocker
     * @param RepositoryInterface $securityTokenRepository
     * @param EntityRepository $paymentRepository
     * @param Client $client
     */
    public function __construct(
        Session $session,
        array $parameters,
        Przelewy24ApiMocker $przelewy24ApiMocker,
        RepositoryInterface $securityTokenRepository,
        EntityRepository $paymentRepository,
        Client $client
    )
    {
        parent::__construct($session, $parameters);

        $this->przelewy24ApiMocker = $przelewy24ApiMocker;
        $this->paymentRepository = $paymentRepository;
        $this->securityTokenRepository = $securityTokenRepository;
        $this->client = $client;
    }

    /**
     * @inheritDoc}
     *
     * @throws \Behat\Mink\Exception\DriverException
     * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
     */
    public function pay(): void
    {
        $captureToken = $this->findToken();
        $notifyToken = $this->findToken('notify');

        $postData = [
            'p24_session_id' => $this->getSessionId($captureToken),
            'p24_order_id' => 'test',
            'p24_sign' => 'test',
            'p24_amount' => 'test',
            'p24_currency' => 'test',
        ];

        $this->przelewy24ApiMocker->mockApiSuccessfulVerifyTransaction(function () use ($notifyToken, $postData) {
           $this->client->request('POST', $notifyToken->getTargetUrl(), $postData);
        });

        $this->getDriver()->visit($captureToken->getAfterUrl());
    }

    /**
     * @inheritDoc}
     *
     * @throws \Behat\Mink\Exception\DriverException
     * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
     */
    public function failedPayment(): void
    {
        $captureToken = $this->findToken();

        $this->getDriver()->visit($captureToken->getAfterUrl() . '&' . http_build_query(['status' => Przelewy24BridgeInterface::CANCELLED_STATUS]));
    }

    /**
     * {@inheritDoc}
     */
    protected function getUrl(array $urlParameters = []): string
    {
        return 'https://sandbox.przelewy24.pl/';
    }

    /**
     * @param string $type
     *
     * @return TokenInterface
     */
    private function findToken(string $type = 'capture'): TokenInterface
    {
        $tokens = [];

        /** @var TokenInterface $token */
        foreach ($this->securityTokenRepository->findAll() as $token) {
            if (strpos($token->getTargetUrl(), $type)) {
                $tokens[] = $token;
            }
        }

        if (count($tokens) > 0) {
            return end($tokens);
        }

        throw new \RuntimeException('Cannot find capture token, check if you are after proper checkout steps');
    }

    /**
     * @param TokenInterface $token
     *
     * @return string
     */
    private function getSessionId(TokenInterface $token): string
    {
        /** @var PaymentInterface $payment */
        $payment = $this->paymentRepository->find($token->getDetails()->getId());

        return $payment->getDetails()['p24_session_id'];
    }
}
