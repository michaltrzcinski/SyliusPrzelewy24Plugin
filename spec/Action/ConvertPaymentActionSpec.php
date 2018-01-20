<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace spec\BitBag\SyliusPrzelewy24Plugin\Action;

use BitBag\SyliusPrzelewy24Plugin\Action\ConvertPaymentAction;
use Doctrine\Common\Collections\ArrayCollection;
use Payum\Core\Action\ActionInterface;
use PhpSpec\ObjectBehavior;
use Payum\Core\Request\Convert;
use Sylius\Bundle\PayumBundle\Provider\PaymentDescriptionProviderInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;

final class ConvertPaymentActionSpec extends ObjectBehavior
{
    function let(PaymentDescriptionProviderInterface $paymentDescriptionProvider): void
    {
        $this->beConstructedWith($paymentDescriptionProvider);
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(ConvertPaymentAction::class);
    }

    function it_implements_action_interface(): void
    {
        $this->shouldHaveType(ActionInterface::class);
    }

    function it_executes(
        Convert $request,
        PaymentInterface $payment,
        OrderInterface $order,
        CustomerInterface $customer,
        ArrayCollection $items,
        \ArrayIterator $arrayIterator,
        PaymentDescriptionProviderInterface $paymentDescriptionProvider
    ): void
    {
        $customer->getEmail()->willReturn('user@example.com');
        $customer->getId()->willReturn(1);

        $items->getIterator()->willReturn($arrayIterator);

        $order->getNumber()->willReturn(000001);
        $order->getCustomer()->willReturn($customer);
        $order->getLocaleCode()->willReturn('pl_PL');
        $order->getCurrencyCode()->willReturn('USD');
        $order->getShippingAddress()->willReturn(null);
        $order->getItems()->willReturn($items);

        $payment->getOrder()->willReturn($order);
        $payment->getId()->willReturn(1);
        $payment->getAmount()->willReturn(445535);
        $payment->getCurrencyCode()->willReturn('PLN');

        $paymentDescriptionProvider->getPaymentDescription($payment)->willReturn('description');

        $request->getSource()->willReturn($payment);
        $request->getTo()->willReturn('array');
        $request->setResult([
            'p24_amount' => 445535,
            'p24_currency' => 'PLN',
            "p24_description" => "description",
            'p24_language' => 'pl_PL',
            'p24_email' => 'user@example.com',
        ])->shouldBeCalled();

        $this->execute($request);
    }

    function it_supports_only_convert_request_payment_source_and_array_to(
        Convert $request,
        PaymentInterface $payment
    ): void
    {
        $request->getSource()->willReturn($payment);
        $request->getTo()->willReturn('array');

        $this->supports($request)->shouldReturn(true);
    }
}
