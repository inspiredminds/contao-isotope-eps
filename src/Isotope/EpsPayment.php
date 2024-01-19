<?php

declare(strict_types=1);

namespace InspiredMinds\ContaoIsotopeEps\Isotope;

use Contao\Database\Result;
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

    private readonly EpsHandler $epsHandler;

    public function __construct(Result|null $result = null)
    {
        parent::__construct($result);

        $this->epsHandler = System::getContainer()->get(EpsHandler::class);
    }

    public function checkoutForm(IsotopeProductCollection $order, Module $module): Response
    {
        return $this->epsHandler->initiate($order, $module, $this, Isotope::getConfig());
    }

    public function getPostsaleOrder(): IsotopeOrderableCollection|null
    {
        return $this->epsHandler->getPostsaleOrder();
    }

    public function processPostsale(IsotopeProductCollection $objOrder): void
    {
        $this->epsHandler->processPostsale($objOrder, $this);
    }
}
