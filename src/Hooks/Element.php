<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

use Contao\CoreBundle\DataContainer\PaletteManipulator;


class Element {


    public function isVisibleElement( $objElement, $blnIsVisible ) {

        $objRequest = \System::getContainer()->get( 'request_stack' )->getCurrentRequest();

        if ( $objRequest === null ) {

            return $blnIsVisible;
        }

        if ( $objRequest->get( '_scope' ) == 'frontend' ) {

            if ( $objElement->cmHideOnDetailPage && $_GET['auto_item'] ) {

                return false;
            }
        }

        return $blnIsVisible;
    }


    public function onloadCallback( \DataContainer $dc ) {

        foreach ( $GLOBALS['TL_DCA'][ $dc->table ]['palettes'] as $strPalette => $strField ) {

            if ( in_array( $strPalette, [ '__selector__', 'default' ] ) ) {

                continue;
            }

            if ( strpos( $strField, 'cmHideOnDetailPage' ) ) {

                continue;
            }

            PaletteManipulator::create()
                ->addField('cmHideOnDetailPage', 'type_legend', PaletteManipulator::POSITION_APPEND )
                ->applyToPalette( $strPalette, 'tl_content');
        }
    }
}