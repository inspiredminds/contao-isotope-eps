<?php

declare(strict_types=1);

/*
 * This file is part of the Contao Isotope eps extension.
 *
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoIsotopeEps;

use at\externet\eps_bank_transfer\BankConfirmationDetails;
use at\externet\eps_bank_transfer\SoCommunicator;
use at\externet\eps_bank_transfer\TransferInitiatorDetails;
use at\externet\eps_bank_transfer\TransferMsgDetails;
use at\externet\eps_bank_transfer\VitalityCheckDetails;
use at\externet\eps_bank_transfer\WebshopArticle;
use Contao\StringUtil;
use InspiredMinds\ContaoIsotopeEps\Isotope\EpsPayment;
use Isotope\Interfaces\IsotopeOrderableCollection;
use Isotope\Interfaces\IsotopePayment;
use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Interfaces\IsotopePurchasableCollection;
use Isotope\Model\Config;
use Isotope\Model\ProductCollection\Order;
use Isotope\Module\Checkout;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use const at\externet\eps_bank_transfer\XMLNS_epsp;

class EpsHandler
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $contaoErrorLogger,
    ) {
    }

    public function initiate(IsotopeProductCollection $order, Checkout $module, IsotopePayment $payment, Config|null $config): Response
    {
        $confirmUrl = $this->urlGenerator->generate('isotope_postsale', ['mod' => 'pay', 'id' => $payment->id], UrlGeneratorInterface::ABSOLUTE_URL);
        $completeUrl = $module->generateUrlForStep('complete', $order, absolute: true);
        $failUrl = $module->generateUrlForStep('failed', $order, absolute: true);

        $transferMsgDetails = new TransferMsgDetails(
            StringUtil::specialcharsAttribute($confirmUrl),
            StringUtil::specialcharsAttribute($completeUrl),
            StringUtil::specialcharsAttribute($failUrl),
        );

        $transferInitiatorDetails = new TransferInitiatorDetails(
            $payment->epsUserId,
            $payment->epsSecret,
            $payment->epsBic ?: $config?->bankCode,
            $payment->epsAccountName ?: implode(' ', array_filter([$config?->firstname, $config?->lastname])),
            $payment->epsIban ?: $config?->bankAccount,
            $order->getUniqueId(),
            (int) ($order->getTotal() * 100),
            $transferMsgDetails,
        );

        $transferInitiatorDetails->RemittanceIdentifier = $order->getUniqueId();

        $transferInitiatorDetails->SetExpirationMinutes(60);

        foreach ($order->getItems() as $item) {
            $transferInitiatorDetails->WebshopArticles[] = new WebshopArticle(
                $item->getName(),
                $item->quantity,
                (int) ($item->getPrice() * 100),
            );
        }

        $soCommunicator = new SoCommunicator((bool) $payment->epsTestMode);
        $plain = $soCommunicator->SendTransferInitiatorDetails($transferInitiatorDetails);

        $xml = new \SimpleXMLElement($plain);
        $soAnswer = $xml->children(XMLNS_epsp);
        $errorDetails = $soAnswer->BankResponseDetails->ErrorDetails;
        $errorCode = (string) $errorDetails->ErrorCode;
        $errorMsg = (string) $errorDetails->ErrorMsg;

        if ('000' !== $errorCode) {
            $this->contaoErrorLogger->error(sprintf('eps payment error for order %s: (%s) %s', $order->getUniqueId(), $errorCode, $errorMsg));

            return new RedirectResponse($failUrl);
        }

        return new RedirectResponse((string) $soAnswer->BankResponseDetails->ClientRedirectUrl);
    }

    public function getPostsaleOrder(): IsotopeOrderableCollection|null
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return null;
        }

        $xml = new \SimpleXMLElement($request->getContent());
        $epspChildren = $xml->children(XMLNS_epsp);
        $firstChildName = $epspChildren[0]->getName();

        if ('VitalityCheckDetails' === $firstChildName) {
            $vitalityCheckDetails = new VitalityCheckDetails($xml);

            return Order::findOneByUniqid($vitalityCheckDetails->GetRemittanceIdentifier());
        }

        if ('BankConfirmationDetails' === $firstChildName) {
            $bankConfirmationDetails = new BankConfirmationDetails($xml);

            return Order::findOneByUniqid($bankConfirmationDetails->GetRemittanceIdentifier());
        }

        return null;
    }

    public function processPostsale(IsotopePurchasableCollection $order, EpsPayment $payment): void
    {
        $soCommunicator = new SoCommunicator((bool) $payment->epsTestMode);
        $soCommunicator->HandleConfirmationUrl(
            function (string $body, BankConfirmationDetails $bankConfirmationDetails) use ($order, $payment) {
                if ($order->getUniqueId() !== $bankConfirmationDetails->GetRemittanceIdentifier()) {
                    throw new \RuntimeException('Remittance identifier does not match unique order ID.');
                }

                if ('OK' === $bankConfirmationDetails->GetStatusCode() && $order->checkout()) {
                    $order->date_paid = time();
                    $order->updateOrderStatus($payment->new_order_status);
                    $order->save();
                } else {
                    $this->contaoErrorLogger->error(sprintf('eps postsale checkout for order ID '.$order->getUniqueId().' failed (status code %s)', $bankConfirmationDetails->GetStatusCode()));
                }

                return true;
            },
        );

        // The SoCommunicator handles the complete input and output
        exit;
    }
}
