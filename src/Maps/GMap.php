<?php

namespace Alnv\ContaoCatalogManagerBundle\Maps;

use Contao\FrontendTemplate;

class GMap extends MapView
{
    /**
     * Das JSON landet direkt in einem <script>-Block. Deshalb werden <, >, &, ' und " als \uXXXX
     * kodiert – sonst bricht z. B. ein Titel "</script><script>…" aus dem Script aus (XSS).
     */
    public const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;

    public function render()
    {
        global $objPage;

        $arrLocations = $this->getLocations();

        // Nur die Kartendaten ausliefern – nicht alle Spalten inkl. origin/interner Felder
        $arrJson = \array_values(\array_map(static function ($arrLocation) {
            return ['map' => $arrLocation['map']];
        }, $arrLocations));

        if ($objPage->ajaxContext) {
            // vorher: json_encode($arrLocations, 512) – 512 wurde fälschlich als Flags übergeben
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
