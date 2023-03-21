<?php

namespace Alnv\ContaoCatalogManagerBundle\Inserttags;

class PageInsertTag {

    public function replace($strFragment) {

        $arrFragments = explode('::', $strFragment);

        if (is_array($arrFragments) && $arrFragments[0] == 'ACTIVE_PAGE') {

            global $objPage;

            $strPageId = $objPage->id;
            $blnUseParent = false;
            $blnTranslate = false;
            $blnReverse = false;

            if (isset($arrFragments[1]) && strpos($arrFragments[1], '?') !== false) {
                $arrParams = \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::parseParametersFromString($arrFragments[1]);
                foreach ($arrParams as $strParam) {
                    list($strKey, $strOption) = explode('=', $strParam);
                    switch ($strKey) {
                        case 'useParent':
                            $blnUseParent = true;
                            break;
                        case 'translate':
                            $blnTranslate = true;
                            break;
                        case 'reverse':
                            $blnReverse = true;
                            break;
                    }
                }
            }

            if ($blnUseParent) {
                $strPageId = $objPage->pid;
            }

            if ($blnTranslate) {
                $objTransPage = \PageModel::findByPk($strPageId);
                $strPageId = $objTransPage ? $objTransPage->languageMain : $objPage->languageMain;
            }

            return serialize($this->getCurrentAndAllSubPages($strPageId, $blnReverse));
        }

        return false;
    }

    protected function getCurrentAndAllSubPages($strId, $blnReverse, &$arrReturn=[]) {

        $objPage = \PageModel::findByPk($strId);

        if ($objPage === null) {
            return $arrReturn;
        }

        if (!$blnReverse) {
            $arrReturn[] = $strId;
            if ($objNext = \PageModel::findPublishedByPid($objPage->id)) {
                while ($objNext->next()) {
                    $this->getCurrentAndAllSubPages($objNext->id, $blnReverse, $arrReturn);
                }
            }
        } else {
            $arrReturn[] = $strId;
            if ($objPrev = \PageModel::findPublishedById($objPage->pid)) {
                $this->getCurrentAndAllSubPages($objPrev->id, $blnReverse, $arrReturn);
            }
        }

        return $arrReturn;
    }
}