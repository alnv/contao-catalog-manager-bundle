<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

class Article {

    public function compileArticle($objTemplate, $arrData, \ModuleArticle $objArticle) {

        if (!$objArticle->cmContentElement) {
            return null;
        }

        $arrElements = [];
        $objCte = \ContentModel::findPublishedByPidAndTable($objArticle->cmContentElement, 'tl_catalog_element');
        if ($objCte !== null) {
            while ($objCte->next()) {
                $arrCss = [];
                $objRow = $objCte->current();
                $objRow->classes = $arrCss;
                $arrElements[] = $objArticle->getContentElement($objRow, $objArticle->strColumn);
            }
        }

        $arrTemplateElements = is_array($objTemplate->elements) ? $objTemplate->elements : [];
        array_insert($arrTemplateElements, ($objArticle->cmContentElementPosition=='before'?0:count($arrTemplateElements)), $arrElements);
        $objTemplate->elements = $arrTemplateElements;
    }
}