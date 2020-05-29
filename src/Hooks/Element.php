<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

use Contao\CoreBundle\DataContainer\PaletteManipulator;

class Element {

    public function isVisibleElement($objElement, $blnIsVisible) {

        $objRequest = \System::getContainer()->get( 'request_stack' )->getCurrentRequest();

        if ( $objRequest === null ) {
            return $blnIsVisible;
        }

        if ($objRequest->get('_scope') == 'frontend') {
            if ($objElement->cmHideOnDetailPage && $_GET['auto_item']) { // backwards
                return false;
            }
            if (!$objElement->cmHide) {
                return $blnIsVisible;
            }
            switch ($objElement->cmHide) {
                case 'autoitem':
                    if ($_GET['auto_item']) {
                        return false;
                    }
                    break;
                case 'default':
                    if (!$_GET['auto_item']) {
                        return false;
                    }
                    break;
            }
        }

        return $blnIsVisible;
    }

    public function onloadCallback( \DataContainer $dc ) {

        foreach ($GLOBALS['TL_DCA'][ $dc->table ]['palettes'] as $strPalette => $strField) {

            if (in_array($strPalette, [ '__selector__', 'default' ])) {
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