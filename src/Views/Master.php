<?php

namespace Alnv\ContaoCatalogManagerBundle\Views;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;

class Master extends View {

    public function parse() {

        $objModel = new ModelWizard($this->strTable);
        $objModel = $objModel->getModel();
        $objEntity = $objModel->findByIdOrAlias($this->arrOptions['alias'], $this->getModelOptions());

        if ($objEntity !== null) {
            $arrEntity = $objEntity->row();
            $this->parseEntity($arrEntity);
            
            \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::addToCatalogData('view-master', $this->strTable, $arrEntity['id']);
            \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::addCount('count-master', $this->strTable, $arrEntity['id']);
        }

        return $this->getEntities();
    }
}