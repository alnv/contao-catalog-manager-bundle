<?php

namespace Alnv\ContaoCatalogManagerBundle\Maps;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Views\Listing;

abstract class MapView
{

    protected null|string $strTable = null;
    protected array $arrOptions = [];

    public function __construct($strTable, $arrOptions = [])
    {

        $this->strTable = $strTable;
        $this->arrOptions = $arrOptions;
        $this->arrOptions['isForm'] = true;
    }

    protected function getLocations()
    {

        return array_map(function ($arrLocation) {
            $arrLocation['map'] = [];
            $arrLocation['map']['street'] = $arrLocation['roleResolver']()->getValueByRole('street');
            $arrLocation['map']['streetNumber'] = $arrLocation['roleResolver']()->getValueByRole('streetNumber');
            $arrLocation['map']['city'] = $arrLocation['roleResolver']()->getValueByRole('city');
            $arrLocation['map']['zip'] = ($arrLocation['roleResolver']()->getValueByRole('zip') ?: $arrLocation['roleResolver']()->getValueByRole('postal'));
            $arrLocation['map']['country'] = $arrLocation['roleResolver']()->getValueByRole('country');
            $arrLocation['map']['location'] = $arrLocation['roleResolver']()->getValueByRole('location');
            $arrLocation['map']['title'] = $arrLocation['roleResolver']()->getValueByRole('title');
            $arrLocation['map']['text'] = $arrLocation['roleResolver']()->getValueByRole('teaser');
            $arrLocation['map']['_distance'] = (((int)$arrLocation['_distance'] ?? 0) > 0 ? \number_format($arrLocation['_distance'], '2', '.') : '');
            $arrLocation['map']['latitude'] = $arrLocation['roleResolver']()->getValueByRole('latitude');
            $arrLocation['map']['longitude'] = $arrLocation['roleResolver']()->getValueByRole('longitude');
            $arrLocation['map']['infoContent'] = Toolkit::replaceInsertTags(Toolkit::parseSimpleTokens($this->arrOptions['infoContent'], $this->parseTokens($arrLocation)));
            return $arrLocation;
        }, (new Listing($this->strTable, $this->arrOptions))->parse());
    }

    protected function parseTokens($arrLocation): array
    {

        $arrTokens = [];

        foreach ($arrLocation as $strField => $varValue) {

            if (\is_callable($varValue)) {
                continue;
            }

            if ($strField == 'origin') {
                continue;
            }

            if ($strField == 'map') {
                continue;
            }

            if ($strField == '_distance' && $varValue) {
                $varValue = \number_format($arrLocation['_distance'], '2', '.');
            }

            if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField]['inputType'] ?? '') == 'fileTree') {
                $varValue = Toolkit::parseImage($varValue);
            }

            if (\is_array($varValue)) {
                $varValue = Toolkit::parse($varValue);
            }

            $arrTokens[$strField] = $varValue;
        }
        return $arrTokens;
    }

    abstract public function render();
}