<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

class FormFields {

    public function compileFormFields($arrFields, $strFormId, $objForm) {

        foreach ($arrFields as $objField) {

            $arrOptions = $this->getOptions($objField->type, $objField);

            if ($arrOptions == null) {
                continue;
            }

            $objField->value = \Input::get($objField->name);
            $objField->options = serialize($arrOptions);
        }

        return $arrFields;
    }

    public function loadFormField(\Widget $objWidget) {

        if (in_array($objWidget->type, ['select','checkbox','radio']) && $objWidget->type) { // @todo

            if (\Input::get($objWidget->name) !== null) {
                $objWidget->value = \Input::get($objWidget->name);
            }
        }
        return $objWidget;
    }

    protected function getOptions($strType, $objField) {

        if (in_array($strType, ['select','checkbox','radio']) && $objField->optionsSource) {
            switch ($objField->optionsSource) {
                case 'dbActiveOptions':
                case 'dbOptions':
                    $objOptions = \Alnv\ContaoCatalogManagerBundle\Library\Options::getInstance($objField->name . '.' . $objField->pid);
                    $objOptions::setParameter($objField->row(), null);
                    $arrOptions = $objOptions::getOptions(true);
                    if ($objField->includeBlankOption) {
                        array_insert($arrOptions, 0, [
                            [
                                'value' => null,
                                'label' => $objField->blankOptionLabel ?: '-'
                            ]
                        ]);
                    }
                    return $arrOptions;
            }
        }

        return null;
    }
}