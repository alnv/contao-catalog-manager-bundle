<?php

namespace Alnv\ContaoCatalogManagerBundle\Entity;

use Alnv\ContaoCatalogManagerBundle\Library\DcaExtractor;
use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Helper\Getters;
use Contao\Widget;
use Contao\Input;

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

        return $arrPageFilter['alias'] ?: $arrPageFilter['column'];
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
            $varValue =  $arrAttribute['value'][0] ?? '';
        } else {
            $varValue =  $arrAttribute['value'] ?? '';
        }

        $arrFragment = is_numeric($varValue) ? $this->getFragmentId($varValue) : [];
        if (!empty($arrFragment)) {
            return $arrFragment[$arrPageFilter['column']] ?? '';
        }

        return $varValue;
    }

    protected function getFragmentId($strFragmentId): array
    {
        $arrPageFilter = $this->getPageFilterArray();
        $strTable = $GLOBALS['TL_DCA'][$arrPageFilter['table']]['config']['_table'] ?? $arrPageFilter['table'];
        $objModel = new ModelWizard($arrPageFilter['table']);
        $objModel = $objModel->getModel();
        $objEntities = $objModel->findAll([
            'column' => [$strTable . '.id=?'],
            'value' => [$strFragmentId],
            'limit' => 1
        ]);

        if (!$objEntities) {
            return [];
        }

        return $objEntities->row();
    }

    protected function activeUrlFragmentExist($strActiveUrlFragment): bool
    {

        $arrPageFilter = $this->getPageFilterArray();
        if (!$arrPageFilter['table'] || !$arrPageFilter['column']) {
            return false;
        }

        $strTable = $GLOBALS['TL_DCA'][$arrPageFilter['table']]['config']['_table'] ?? $arrPageFilter['table'];
        $arrColumn = [$strTable . '.' . $arrPageFilter['column'] . ' REGEXP ?'];
        $arrValue = ['[[:<:]]'. $strActiveUrlFragment .'[[:>:]]'];

        $objModel = new ModelWizard($arrPageFilter['table']);
        $objModel = $objModel->getModel();
        $objEntities = $objModel->findAll([
            'column' => $arrColumn,
            'value' => $arrValue,
            'limit' => 1
        ]);

        return (bool) $objEntities;
    }

    public function setActiveUrlFragment(string $strActiveUrlFragment): void
    {

        if (!$strActiveUrlFragment) {
            return;
        }

        if (!$this->activeUrlFragmentExist($strActiveUrlFragment)) {
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