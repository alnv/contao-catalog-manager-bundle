<?php

namespace Alnv\ContaoCatalogManagerBundle\Inserttags;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;


class ActiveInsertTag {


    public function replace( $strFragment ) {

        $arrFragments = explode( '::', $strFragment );

        if ( is_array( $arrFragments ) && $arrFragments[0] == 'ACTIVE' && isset( $arrFragments[1] ) ) {

            $varValue = Toolkit::getValueFromUrl( \Input::get( $arrFragments[1] ) );

            if ( isset( $arrFragments[2] ) && strpos( $arrFragments[2], '?' ) !== false ) {

                $arrParams = Toolkit::parseParametersFromString( $arrFragments[2] );

                foreach ( $arrParams as $strParam ) {

                    list( $strKey, $strOption ) = explode( '=', $strParam );

                    switch ( $strKey ) {

                        case 'default':

                            //

                            break;
                    }
                }
            }

            return $varValue;
        }


        return false;
    }
}