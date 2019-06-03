<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;


class DcaExtractor extends \DcaExtractor {


    public function __construct( $strTable ) {

        parent::__construct( $strTable );
    }


    public function getOrderBy() {

        $arrOrderBy = [];

        if ( !isset( $GLOBALS['TL_DCA'][ $this->strTable ]['list'] ) ) {

            return $arrOrderBy;
        }

        if ( !isset( $GLOBALS['TL_DCA'][ $this->strTable ]['list']['sorting'] ) ) {

            return $arrOrderBy;
        }

        if ( !$GLOBALS['TL_DCA'][ $this->strTable ]['list']['sorting']['mode'] ) {

            return $arrOrderBy;
        }

        switch ( $GLOBALS['TL_DCA'][ $this->strTable ]['list']['sorting']['mode'] ) {

            case 1:

                $arrOrderBy = [];
                $strFlag = 'ASC';

                if ( isset( $GLOBALS['TL_DCA'][ $this->strTable ]['list']['sorting']['flag'] ) ) {

                    $strFlag = $GLOBALS['TL_DCA'][ $this->strTable ]['list']['sorting']['flag'] % 2 == 0 ? 'DESC' : 'ASC';
                }

                if ( is_array( $GLOBALS['TL_DCA'][ $this->strTable ]['list']['sorting']['fields'] ) && !empty( $GLOBALS['TL_DCA'][ $this->strTable ]['list']['sorting']['fields'] ) ) {

                    foreach ( $GLOBALS['TL_DCA'][ $this->strTable ]['list']['sorting']['fields'] as $strField ) {

                        $arrOrder = explode( ' ', $strField );

                        $arrOrderBy[] = [

                            'field' => $arrOrder[0],
                            'order' => $arrOrder[1] ?: $strFlag
                        ];
                    }

                    return $arrOrderBy;
                }

                break;

            case 2:

                //

                break;

            case 3:

                // do not support
                return $arrOrderBy;

                break;

            case 4:

                //

                break;

            case 5:
            case 6:

                //

                break;
        }

        return $arrOrderBy;
    }
}