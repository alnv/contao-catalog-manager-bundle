<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Library\Options;
use Contao\ArrayUtil;

class Widget
{

    public function getAttributesFromDca($arrAttributes, $objDataContainer = null)
    {

        if (isset($arrAttributes['optionsSource']) && $arrAttributes['optionsSource']) {
            switch ($arrAttributes['optionsSource']) {
                case 'dbOptions':
                    $objOptions = Options::getInstance($arrAttributes['name'] . '.' . $arrAttributes['pid']);
                    $objOptions::setParameter($arrAttributes, $objDataContainer);
                    $arrAttributes['options'] = $objOptions::getOptions(true);
                    if ($arrAttributes['includeBlankOption']) {
                        ArrayUtil::arrayInsert($arrAttributes['options'], 0, [
                            [
                                'value' => null,
                                'label' => Toolkit::replaceInsertTags($arrAttributes['blankOptionLabel']) ?: '-'
                            ]
                        ]);
                    }
                    break;
            }
        }

        return $arrAttributes;
    }
}