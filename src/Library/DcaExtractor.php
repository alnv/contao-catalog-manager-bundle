<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

class DcaExtractor extends \DcaExtractor {

    public function __construct($strTable) {

        parent::__construct($strTable);
    }

    public function getOrderBy() {

        if (!isset($GLOBALS['TL_DCA'][ $this->strTable ]['list'])) {
            return '';
        }

        if (!isset($GLOBALS['TL_DCA'][ $this->strTable ]['list']['sorting'])) {
            return '';
        }

        if (!$GLOBALS['TL_DCA'][ $this->strTable ]['list']['sorting']['mode']) {
            return '';
        }

        switch ($GLOBALS['TL_DCA'][ $this->strTable ]['list']['sorting']['mode']) {
            case 1:
                $arrOrderBy = [];
                $strFlag = 'ASC';
                if ( isset($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'])) {
                    $strFlag = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] % 2 == 0 ? 'DESC' : 'ASC';
                }

                if (is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields']) && !empty($GLOBALS['TL_DCA'][ $this->strTable ]['list']['sorting']['fields'])) {
                    foreach ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'] as $strField) {
                        $strTable = $this->strTable;
                        if ($this->getDataContainer() == 'Multilingual') {
                            // $strTable = 'translation';
                        }
                        $arrOrder = explode(' ', $strField);
                        $arrOrderBy[] = $strTable . '.' . $arrOrder[0] . ' ' . strtoupper($arrOrder[1] ?: $strFlag);
                    }
                    return implode(' ', $arrOrderBy);
                }
                break;
            case 2:
                return '';
            case 3:
                // do not support
                return '';
            case 4:
                return '';
            case 5:
            case 6:
                return '';
        }

        return '';
    }

    public function hasVisibility() {

        $objCatalog = \Alnv\ContaoCatalogManagerBundle\Models\CatalogModel::findByTableOrModule($this->strTable);

        if ($objCatalog !== null) {

            return (bool) $objCatalog->enableVisibility;
        }

        return isset($GLOBALS['TL_DCA'][$this->strTable]['fields']['start']) && isset($GLOBALS['TL_DCA'][$this->strTable]['fields']['stop']) && isset($GLOBALS['TL_DCA'][$this->strTable]['fields']['published']);
    }

    public function getDataContainer() {

        return $GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'];
    }

    public function getField($strFieldname) {

        return $GLOBALS['TL_DCA'][$this->strTable]['fields'][$strFieldname] ?? [];
    }
}