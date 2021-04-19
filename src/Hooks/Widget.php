<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

class Widget {

    public function getAttributesFromDca($arrAttributes, $objDataContainer=null) {

        if (isset($arrAttributes['optionsSource']) && $arrAttributes['optionsSource']) {
            switch ($arrAttributes['optionsSource']) {
                case 'dbOptions':
                    $objOptions = \Alnv\ContaoCatalogManagerBundle\Library\Options::getInstance( $arrAttributes['name'] . '.' . $arrAttributes['pid'] );
                    $objOptions::setParameter($arrAttributes, $objDataContainer);
                    $arrAttributes['options'] = $objOptions::getOptions(true);
                    if ($arrAttributes['includeBlankOption']) {
                        array_insert($arrAttributes['options'], 0, [
                            [
                                'value' => null,
                                'label' => \Controller::replaceInsertTags($arrAttributes['blankOptionLabel']) ?: '-'
                            ]
                        ]);
                    }
                    break;
            }
        }

        return $arrAttributes;
    }
}