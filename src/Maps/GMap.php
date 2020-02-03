<?php

namespace Alnv\ContaoCatalogManagerBundle\Maps;

use function Clue\StreamFilter\fun;

class GMap extends MapView {

    public function render() {

        $arrLocations = $this->getLocations();
        $objTemplate = new \FrontendTemplate('cm_map_view_gmap');
        $objTemplate->setData([
            'locations' => $arrLocations,
            'varLocations' => json_encode( $arrLocations, 512 )
        ]);
        return $objTemplate->parse();
    }
}