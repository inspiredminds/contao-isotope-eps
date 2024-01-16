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
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Util\UrlUtil;
use Contao\StringUtil;
use Isotope\Interfaces\IsotopePayment;
use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Model\Config;
use Isotope\Module\Checkout;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class EpsHandler
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $contaoErrorLogger,
    ) {
    }

    public function initiate(IsotopeProductCollection $order, Checkout $module, IsotopePayment $payment, Config|null $config): void
    {
        $failUrl = $this->getAbsoluteUrl($module->generateUrlForStep('failed', $order));

        $transferMsgDetails = new TransferMsgDetails(
            StringUtil::specialcharsAttribute($this->getAbsoluteUrl('/system/modules/isotope/postsale.php?mod=pay&id='.$payment->getId())),
            StringUtil::specialcharsAttribute($this->getAbsoluteUrl($module->generateUrlForStep('complete', $order))),
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

        $dom = new \DOMDocument();
        $dom->loadXML($plain);

        $errorCode = $dom->getElementsByTagName('ErrorCode')->item(0)?->nodeValue;
        $errorMsg = $dom->getElementsByTagName('ErrorMsg')->item(0)?->nodeValue;
        $redirectUrl = $dom->getElementsByTagName('ClientRedirectUrl')->item(0)?->nodeValue;

        if ('000' !== $errorCode) {
            $this->contaoErrorLogger->error(sprintf('eps payment error #%s: %s', $errorCode, $errorMsg));

            throw new RedirectResponseException($failUrl);
        }

        throw new RedirectResponseException($redirectUrl);
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
