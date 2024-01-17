<?php

declare(strict_types=1);

namespace InspiredMinds\ContaoIsotopeEps\Isotope;

use Contao\Module;
use Contao\System;
use InspiredMinds\ContaoIsotopeEps\EpsHandler;
use Isotope\Interfaces\IsotopeOrderableCollection;
use Isotope\Interfaces\IsotopePayment;
use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Isotope;
use Isotope\Model\Payment\Postsale;
use Symfony\Component\HttpFoundation\Response;

class EpsPayment extends Postsale implements IsotopePayment
{
    final public const TYPE = 'eps';

    public function checkoutForm(IsotopeProductCollection $order, Module $module): Response
    {
        /** @var EpsHandler $epsHandler */
        $epsHandler = System::getContainer()->get(EpsHandler::class);
        
        return $epsHandler->initiate($order, $module, $this, Isotope::getConfig());
    }

    public function getPostsaleOrder(): IsotopeOrderableCollection|null
    {
        return null;
    }

    public function processPostsale(IsotopeProductCollection $objOrder): Response|null
    {
        return null;
    }
}
