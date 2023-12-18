<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\DataContainer;
use Contao\Model;
use Contao\System;

class IsVisibleElementListener
{

    public function __invoke(Model $objElement, bool $blnIsVisible): bool
    {
        return $this->isVisibleElement($objElement, $blnIsVisible);
    }

    public function isVisibleElement(&$objElement, $blnIsVisible)
    {

        $objRequest = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($objRequest === null) {
            return $blnIsVisible;
        }

        if ($objRequest->get('_scope') == 'frontend') {

            $strAutoItem = $_GET['auto_item'] ?? '';

            if (!$objElement->cmHide) {
                return $blnIsVisible;
            }

            switch ($objElement->cmHide) {
                case 'autoitem':
                    if ($strAutoItem) {
                        return false;
                    }
                    break;
                case 'default':
                    if (!$strAutoItem) {
                        return false;
                    }
                    break;
            }
        }

        return $blnIsVisible;
    }

    public function onloadCallback(DataContainer $dc): void
    {

        foreach ($GLOBALS['TL_DCA'][$dc->table]['palettes'] as $strPalette => $strField) {

            if (in_array($strPalette, ['__selector__', 'default'])) {
                continue;
            }

            if (strpos($strField, 'cmHide')) {
                continue;
            }

            PaletteManipulator::create()
                ->addField('cmHide', 'type_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToPalette($strPalette, 'tl_content');
        }
    }
}