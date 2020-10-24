<?php

namespace Alnv\ContaoCatalogManagerBundle\Maps;

class GMap extends MapView {

    public function render() {

        global $objPage;

        $arrLocations = $this->getLocations();

        if ($objPage->ajaxContext) {
            return json_encode($arrLocations, 512);
        }

        $objTemplate = new \FrontendTemplate('cm_map_view_gmap');
        $objTemplate->setData([
            'locations' => $arrLocations,
            'varLocations' => json_encode($arrLocations, 512)
        ]);

        return $objTemplate->parse();
    }
}