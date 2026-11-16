<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Alnv\ContaoCatalogManagerBundle\Library\Options;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Contao\ArrayUtil;
use Contao\Form;
use Contao\Input;

class CompileFormFieldsListener
{

    public function __invoke(array $arrFields, string $strFormId, Form $objForm): array
    {
        foreach ($arrFields as $objField) {

            $arrOptions = $this->getOptions($objField->type, $objField);

            if ($arrOptions == null) {
                continue;
            }

            $objField->value = Input::get($objField->name);
            $objField->options = serialize($arrOptions);
        }

        return $arrFields;
    }

    protected function getOptions($strType, $objField)
    {

        if (in_array($strType, ['select', 'checkbox', 'radio']) && $objField->optionsSource) {
            switch ($objField->optionsSource) {
                case 'dbActiveOptions':
                case 'dbOptions':
                    $objOptions = Options::getInstance($objField->name . '.' . $objField->pid);
                    $objOptions::setParameter($objField->row(), null);
                    $arrOptions = $objOptions::getOptions(true);
                    if ($objField->includeBlankOption) {
                        ArrayUtil::arrayInsert($arrOptions, 0, [
                            [
                                'value' => null,
                                'label' => Toolkit::replaceInsertTags($objField->blankOptionLabel) ?: '-'
                            ]
                        ]);
                    }
                    return $arrOptions;
            }
        }

        return null;
    }
}