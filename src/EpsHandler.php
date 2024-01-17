<?php

declare(strict_types=1);

/*
 * This file is part of the Contao Isotope eps extension.
 *
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoIsotopeEps;

use at\externet\eps_bank_transfer\SoCommunicator;
use at\externet\eps_bank_transfer\TransferInitiatorDetails;
use at\externet\eps_bank_transfer\TransferMsgDetails;
use at\externet\eps_bank_transfer\WebshopArticle;
use Contao\CoreBundle\Util\UrlUtil;
use Contao\StringUtil;
use Isotope\Interfaces\IsotopePayment;
use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Model\Config;
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
        $completeUrl = $this->getAbsoluteUrl($module->generateUrlForStep('complete', $order));
        $failUrl = $this->getAbsoluteUrl($module->generateUrlForStep('failed', $order));

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
            $payment->epsIban ?: $config->bankAccount,
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

    private function getAbsoluteUrl(string $url): string
    {
        return UrlUtil::makeAbsolute($url, $this->getBaseUrl());
    }

    private function getBaseUrl(): string
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return '';
        }

        return $request->getSchemeAndHttpHost().$request->getBasePath().'/';
    }
}
