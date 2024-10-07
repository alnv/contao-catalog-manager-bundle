<?php

namespace Alnv\ContaoCatalogManagerBundle\Entity;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Views\Listing;
use Alnv\ContaoOpenAiAssistantBundle\Library\Automator;
use Contao\Database;
use Contao\Dbafs;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;

class CatalogVectorFile
{

    protected string $strCatalogVectorFileId;

    protected array $arrCatalogVectorFile = [];

    public function __construct($strCatalogVectorFileId)
    {

        $this->strCatalogVectorFileId = $strCatalogVectorFileId;

        $this->setVectorFile();
    }

    protected function setVectorFile(): array
    {

        $arrEntity = Database::getInstance()->prepare('SELECT * FROM tl_catalog_vector_files WHERE id=?')->limit(1)->execute($this->strCatalogVectorFileId)->row();

        foreach ($arrEntity as $strField => $strValue) {

            switch ($strField) {
                case 'dbTable':
                case 'name':
                    $this->arrCatalogVectorFile[$strField] = $strValue;
                    break;
                case 'fields':
                    $this->arrCatalogVectorFile[$strField] = StringUtil::deserialize($strValue, true);
                    break;
                case 'dbWizardFilterSettings':
                    $arrFilterSettings = Toolkit::convertComboWizardToModelValues($strValue, $arrEntity['dbTable'] ?: '');
                    if (!empty($arrFilterSettings)) {
                        $this->arrCatalogVectorFile['column'] = $arrFilterSettings['column'] ?? [];
                        $this->arrCatalogVectorFile['value'] = $arrFilterSettings['value'] ?? [];
                    }
                    break;
                case 'masterPage':
                    $this->arrCatalogVectorFile['masterPage'] = (int)$strValue;
                    break;
                case 'template':
                    $this->arrCatalogVectorFile['template'] = $strValue;
                    break;
                case 'file':
                    $objFile = FilesModel::findByUuid($strValue);
                    $this->arrCatalogVectorFile[$strField] = $objFile ? $objFile->path : '';
                    break;
            }
        }

        return $this->arrCatalogVectorFile;
    }

    public function update(): void
    {
        Automator::updateVectorStoresByFilePath($this->arrCatalogVectorFile['file'], $this->arrCatalogVectorFile['name']);
    }

    public function save($strFolder = ''): string
    {

        $strText = "";
        $strRootDir = System::getContainer()->getParameter('kernel.project_dir');

        $arrModeOptions = [
            'fastMode' => true,
            'stringMode' => true,
            'useAbsoluteUrl' => true
        ];

        if (isset($this->arrCatalogVectorFile['masterPage']) && $this->arrCatalogVectorFile['masterPage']) {
            $arrModeOptions['masterPage'] = $this->arrCatalogVectorFile['masterPage'];
        }

        if (isset($this->arrCatalogVectorFile['column']) || isset($this->arrCatalogVectorFile['value'])) {
            $arrModeOptions['column'] = $this->arrCatalogVectorFile['column'];
            $arrModeOptions['value'] = $this->arrCatalogVectorFile['value'];
        }

        $arrEntities = (new Listing($this->arrCatalogVectorFile['dbTable'], $arrModeOptions))->parse();

        if ($strFolder && !\file_exists($strRootDir . '/' . $strFolder)) {
            \mkdir($strRootDir . '/' . $strFolder);
            Dbafs::addResource($strFolder);
        }

        System::loadLanguageFile($this->arrCatalogVectorFile['dbTable']);

        foreach ($arrEntities as $arrEntity) {

            $strText .= 'CATALOG-ID: ' . $arrEntity['id'] . PHP_EOL;
            $strText .= 'CATALOG-TEMPLATE: ' . ($this->arrCatalogVectorFile['template'] ?? '') . PHP_EOL;
            $strText .= 'CATALOG-TABLE: ' . ($this->arrCatalogVectorFile['dbTable'] ?? '') . PHP_EOL;

            if (isset($arrEntity['masterUrl']) && $arrEntity['masterUrl']) {
                $strText .= 'CATALOG-URL: ' . $arrEntity['masterUrl'] . PHP_EOL;
            }

            foreach ($arrEntity as $strField => $strValue) {
                if (!empty($this->arrCatalogVectorFile['fields']) && !in_array($strField, $this->arrCatalogVectorFile['fields'])) {
                    continue;
                }

                $strLabel = $GLOBALS['TL_LANG'][$this->arrCatalogVectorFile['dbTable']][$strField][0] ?? $strField;
                $strText .= $strLabel . ': ' . $this->parseString($strValue) . PHP_EOL;
            }

            $strText .= PHP_EOL . PHP_EOL;
        }

        $strFileName = StringUtil::generateAlias($this->arrCatalogVectorFile['name']);

        file_put_contents($strRootDir . '/' . $strFolder . '/' . $strFileName . '.txt', $strText);

        $objFile = Dbafs::addResource($strFolder . '/' . $strFileName . '.txt');

        return $objFile ? StringUtil::binToUuid($objFile->uuid) : '';
    }

    protected function parseString($varValue): string
    {

        if (is_array($varValue)) {
            $varValue = implode(', ', $varValue);
        }

        $varValue = strip_tags($varValue);
        $varValue = str_replace('"', '', $varValue);
        $varValue = str_replace(',', ' ', $varValue);
        $varValue = str_replace('\'', '', $varValue);
        $varValue = str_replace(["\r", "\n"], ' ', $varValue);
        $varValue = str_replace("&nbsp;", '', $varValue);
        $varValue = StringUtil::decodeEntities($varValue);
        $varValue = mb_convert_encoding($varValue, 'UTF-8');

        return trim($varValue);
    }
}