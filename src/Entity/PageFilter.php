<?php

namespace Alnv\ContaoCatalogManagerBundle\Entity;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Contao\Database;
use Contao\Input;
use Alnv\ContaoCatalogManagerBundle\Helper\Getters;
use Alnv\ContaoCatalogManagerBundle\Library\DcaExtractor;
use Contao\Widget;

class PageFilter
{

    protected array $arrPageFilter;

    protected string $strActiveUrlFragment = '';

    public function __construct($strPageFilterId)
    {
        $this->arrPageFilter = Getters::getPageFilterById($strPageFilterId);
    }

    public function getPageFilterArray(): array
    {
        return $this->arrPageFilter;
    }

    public function getFieldName(): string
    {
        $arrPageFilter = $this->getPageFilterArray();

        return $arrPageFilter['column'] ?? '';
    }

    public function getAlias()
    {
        $arrPageFilter = $this->getPageFilterArray();

        return $arrPageFilter['alias'] ?? '';
    }

    public function getActiveUrlFragment(): string
    {
        return $this->strActiveUrlFragment;
    }

    public function parseActiveUrlFragment(string $strActiveUrlFragment): string
    {

        $arrPageFilter = $this->getPageFilterArray();
        $objDcaExtractor = new DcaExtractor($arrPageFilter['table']);
        $arrAttribute = Widget::getAttributesFromDca($objDcaExtractor->getField($arrPageFilter['column']), $arrPageFilter['column'], $strActiveUrlFragment, $arrPageFilter['column'], $arrPageFilter['table']);

        if (is_array($arrAttribute['value'])) {
            return $arrAttribute['value'][0] ?? '';
        } else {
            return $arrAttribute['value'];
        }
    }

    public function setActiveUrlFragment(string $strActiveUrlFragment): void
    {

        if (!$strActiveUrlFragment) {
            return;
        }

        $arrPageFilter = $this->getPageFilterArray();
        $objDcaExtractor = new DcaExtractor($arrPageFilter['table']);

        $varValue = '';
        $arrAttribute = Widget::getAttributesFromDca($objDcaExtractor->getField($arrPageFilter['column']), $arrPageFilter['column'], $strActiveUrlFragment, $arrPageFilter['column'], $arrPageFilter['table']);
        $varParsedValue = Toolkit::parseCatalogValue($strActiveUrlFragment, $arrAttribute);

        if (is_array($varParsedValue)) {
            foreach ($varParsedValue as $arrValues) {
                if ($strActiveUrlFragment == $arrValues['value']) {
                    $varValue = $arrValues['value'];
                }
            }
        } else {
            $varValue = $varParsedValue;
        }

        Input::setGet($arrPageFilter['alias'], $varValue);
        $this->strActiveUrlFragment = $varValue;
    }

    public function activeUrlFragmentExists(): bool
    {
        return (bool) $this->strActiveUrlFragment;
    }
}