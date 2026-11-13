<?php

namespace Alnv\ContaoCatalogManagerBundle\Maps;

use Contao\FrontendTemplate;

class GMap extends MapView
{
    public const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;

    public function render()
    {
        global $objPage;

        $arrLocations = $this->getLocations();

        $arrJson = \array_values(\array_map(static function ($arrLocation) {
            return ['map' => $arrLocation['map']];
        }, $arrLocations));

        if ($objPage->ajaxContext) {
            return \json_encode($arrJson, self::JSON_FLAGS);
        }

        $objTemplate = new FrontendTemplate($this->arrOptions['template']);
        $objTemplate->setData([
            'locations' => $arrLocations,
            'varLocations' => \json_encode($arrJson, self::JSON_FLAGS) ?: '[]'
        ]);

        return $objTemplate->parse();
    }
}
