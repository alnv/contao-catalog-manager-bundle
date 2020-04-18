<?php

namespace Alnv\ContaoCatalogManagerBundle\Inserttags;

class PageInsertTag {

    public function replace($strFragment) {

        $arrFragments = explode('::', $strFragment);

        if (is_array($arrFragments) && $arrFragments[0] == 'ACTIVE_PAGE') {

            global $objPage;

            return serialize($this->getCurrentAndAllSubPages($objPage->id));
        }

        return false;
    }

    protected function getCurrentAndAllSubPages($strId) {

        $objPage = \PageModel::findByPk($strId);
        if ($objPage === null) {
            return [];
        }
        $arrReturn = [];
        $arrReturn[] = $strId;
        if ($objNext = \PageModel::findPublishedByPid($objPage->id)) {
            while ($objNext->next()) {
                $arrReturn[] = $objNext->id;
                $this->getCurrentAndAllSubPages($objNext->id, $arrReturn);
            }
        }

        return $arrReturn;
    }
}