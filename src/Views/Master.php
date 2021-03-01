<?php

namespace Alnv\ContaoCatalogManagerBundle\Views;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;

class Master extends View {

    public function parse() {

        $objModel = new ModelWizard($this->strTable);
        $objModel = $objModel->getModel();
        $objEntity = $objModel->findByIdOrAlias($this->arrOptions['alias'], $this->getModelOptions());

        if ($objEntity !== null) {
            $this->parseEntity($objEntity->row());
        }

        return $this->getEntities();
    }
}