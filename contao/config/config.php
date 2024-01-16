<?php

declare(strict_types=1);

/*
 * This file is part of the Contao Isotope eps extension.
 *
 * (c) INSPIRED MINDS
 */

use InspiredMinds\ContaoIsotopeEps\Isotope\EpsPayment;
use Isotope\Model\Payment;

Payment::registerModelType(EpsPayment::TYPE, EpsPayment::class);
