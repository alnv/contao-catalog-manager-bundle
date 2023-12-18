<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Contao\Form;
use Contao\Input;
use Contao\Widget;

class LoadFormFieldListener
{

    public function __invoke(Widget $objWidget, string $formId, array $formData, Form $form): Widget
    {
        if (in_array($objWidget->type, ['select', 'checkbox', 'radio']) && $objWidget->type) { // @todo

            if (Input::get($objWidget->name) !== null) {
                $objWidget->value = Input::get($objWidget->name);
            }
        }

        return $objWidget;
    }
}