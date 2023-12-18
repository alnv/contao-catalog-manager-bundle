<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Library\Options;
use Contao\ArrayUtil;

class GetAttributesFromDcaListener
{

    public function __invoke(array $arrAttributes, $objDataContainer = null): array
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