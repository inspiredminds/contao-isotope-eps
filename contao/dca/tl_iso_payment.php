<?php

declare(strict_types=1);

/*
 * This file is part of the Contao Isotope eps extension.
 *
 * (c) INSPIRED MINDS
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use InspiredMinds\ContaoIsotopeEps\Isotope\EpsPayment;

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['epsUserId'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['epsSecret'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['epsIban'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['epsBic'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['epsAccountName'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['epsTestMode'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_iso_payment']['palettes'][EpsPayment::TYPE] = $GLOBALS['TL_DCA']['tl_iso_payment']['palettes']['cash'];

PaletteManipulator::create()
    ->addLegend('gateway_legend', 'price_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField('epsUserId', 'gateway_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('epsSecret', 'gateway_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('epsIban', 'gateway_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('epsBic', 'gateway_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('epsAccountName', 'gateway_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('epsTestMode', 'gateway_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette(EpsPayment::TYPE, 'tl_iso_payment')
;
