<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Views\Listing;
use Contao\Dbafs;
use Contao\StringUtil;
use Contao\System;

class Export
{

    protected array $arrEntities = [];

    protected string $strFilename;

    protected string $strFolder;

    public function __construct($strFilename, $strFolder = 'files/_vectors')
    {

        $strRootDir = System::getContainer()->getParameter('kernel.project_dir');

        if (!\file_exists($strRootDir . '/' . $strFolder)) {

            \mkdir($strRootDir . '/' . $strFolder);

            Dbafs::addResource($strFolder);
        }

        $this->strFolder = $strFolder;
        $this->strFilename = $strFilename;
    }

    public function byTable($strTable, $arrOptions): Export
    {

        System::loadLanguageFile($strTable);

        foreach ((new Listing($strTable, [
            'fastMode' => true,
            'stringMode' => true,
            'limit' => $arrOptions['limit'] ?? 1000
        ]))->parse() as $arrEntity) {

            $arrSet = [];
            foreach ($arrEntity as $strField => $varValue) {

                if (in_array($strField, ($arrOptions['ignore_fields'] ?? []))) {
                    continue;
                }

                if (is_callable($varValue)) {
                    continue;
                }

                if (is_array($varValue)) {
                    $varValue = Toolkit::parse($varValue, ' | ');
                }


                $strLabel = ($GLOBALS['TL_LANG'][$strTable][$strField][0] ?? '') ?: $strField;
                $arrSet[$this->parseString($strLabel)] = $this->parseString($varValue);
            }

            $this->arrEntities[] = $arrSet;
        }

        return $this;
    }

    public function prompt(): string
    {

        $strText = "";
        $strRootDir = System::getContainer()->getParameter('kernel.project_dir');

        foreach ($this->arrEntities as $arrEntity) {
            foreach ($arrEntity as $strField => $strValue) {
                $strText .= $strField . ': ' . ($strValue ?: '-') . '; ';
            }
            $strText .= PHP_EOL . PHP_EOL . PHP_EOL;
        }

        file_put_contents($strRootDir . '/' . $this->strFolder . '/' . $this->strFilename . '.txt', $strText);

        Dbafs::addResource($this->strFolder . '/' . $this->strFilename . '.txt');

        return $strText;
    }

    protected function parseString($varValue): string
    {
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