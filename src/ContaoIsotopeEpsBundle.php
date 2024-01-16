<?php

declare(strict_types=1);

/*
 * This file is part of the Contao Isotope eps extension.
 *
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoIsotopeEps;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoIsotopeEpsBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
