<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;


class DcaExtractor extends \DcaExtractor {


    public function __construct( $strTable ) {

        parent::__construct( $strTable );
    }


    public function getOrderBy() {

        if ( !isset( $GLOBALS['TL_DCA'][ $this->strTable ]['list'] ) ) {

            return '';
        }

        if ( !isset( $GLOBALS['TL_DCA'][ $this->strTable ]['list']['sorting'] ) ) {

            return '';
        }

        if ( !$GLOBALS['TL_DCA'][ $this->strTable ]['list']['sorting']['mode'] ) {

            return '';
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

                        $strTable = $this->strTable;

                        if ( $this->getDataContainer() == 'Multilingual' ) {

                            $strTable = 't1';
                        }

                        $arrOrder = explode( ' ', $strField );
                        $arrOrderBy[] = $strTable . '.' . $arrOrder[0] . ' ' . strtoupper( $arrOrder[1] ?: $strFlag );
                    }

                    return implode( ' ', $arrOrderBy );
                }

                break;

            case 2:

                return '';

                break;

            case 3:

                // do not support
                return '';

                break;

            case 4:

                return '';

                break;

            case 5:
            case 6:

                return '';

                break;
        }

        return '';
    }


    public function getDataContainer() {

        return $GLOBALS['TL_DCA'][ $this->strTable ]['config']['dataContainer'];
    }


    public function getField( $strFieldname ) {

        return $GLOBALS['TL_DCA'][ $this->strTable ]['fields'][ $strFieldname ];
    }
}