<?php

namespace Alnv\ContaoCatalogManagerBundle\Maps;

use Contao\FrontendTemplate;

class GMap extends MapView
{

    public function render()
    {

        global $objPage;

        $arrLocations = $this->getLocations();

        if ($objPage->ajaxContext) {
            return \json_encode($arrLocations, 512);
        }

        $objTemplate = new FrontendTemplate($this->arrOptions['template']);
        $arrJson = \array_map(function ($arrLocation) {
            return ['map' => $arrLocation['map']];
        }, $arrLocations);

        $objTemplate->setData([
            'locations' => $arrLocations,
            'varLocations' => \json_encode($arrJson, 0, 512)
        ]);

        return $objTemplate->parse();
    }
}