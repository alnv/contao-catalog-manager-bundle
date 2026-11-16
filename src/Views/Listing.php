<?php

namespace Alnv\ContaoCatalogManagerBundle\Views;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;

class Listing extends View
{

    public function parse(): array
    {

        $objModel = new ModelWizard($this->strTable);
        $objModel = $objModel->getModel();
        $objEntities = $objModel->findAll($this->getModelOptions());

        // Tabellen-Tag auch für leere Listen, damit neu angelegte Einträge die Seite invalidieren
        $this->addCacheTags([]);

        if ($objEntities !== null) {
            while ($objEntities->next()) {
                $this->parseEntity($objEntities->row());
            }
        }

        return $this->getEntities();
    }

    public function countRows(): int
    {

        $objModel = (new ModelWizard($this->strTable))->getModel();

        return $this->countEntities($objModel, $this->getModelOptions());
    }
}