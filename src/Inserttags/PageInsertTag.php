<?php

namespace Alnv\ContaoCatalogManagerBundle\Inserttags;

class PageInsertTag {

    public function replace($strFragment) {

        $arrFragments = explode('::', $strFragment);

        if (is_array($arrFragments) && $arrFragments[0] == 'ACTIVE_PAGE') {

            global $objPage;

            $strPageId = $objPage->id;
            if (isset($arrFragments[1]) && strpos($arrFragments[1], '?') !== false) {
                $arrParams = \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::parseParametersFromString($arrFragments[1]);
                foreach ($arrParams as $strParam) {
                    list($strKey, $strOption) = explode('=', $strParam);
                    switch ($strKey) {
                        case 'useParent':
                            $strPageId = $objPage->pid;
                            break;
                        case 'translate':
                            if ($objPage->languageMain) {
                                $strPageId = $objPage->languageMain;
                            }
                            break;
                    }
                }
            }

            return serialize($this->getCurrentAndAllSubPages($strPageId));
        }

        return false;
    }

    protected function getCurrentAndAllSubPages($strId, &$arrReturn=[]) {

        $objPage = \PageModel::findByPk($strId);
        if ($objPage === null) {
            return $arrReturn;
        }
        $arrReturn[] = $strId;
        if ($objNext = \PageModel::findPublishedByPid($objPage->id)) {
            while ($objNext->next()) {
                $this->getCurrentAndAllSubPages($objNext->id, $arrReturn);
            }
        }

        return $arrReturn;
    }
}