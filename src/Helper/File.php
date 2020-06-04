<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

class File {

    public static function getFile($strUuid, &$arrFiles=[]) {

        $arrValues = \StringUtil::deserialize($strUuid, true);
        $objFiles = \FilesModel::findMultipleByUuids($arrValues);
        if ($objFiles === null) {
            return $arrFiles;
        }

        $objContainer = \System::getContainer();
        $allowedDownload = \StringUtil::trimsplit(',', strtolower(\Config::get('allowedDownload')));

        while ($objFiles->next()) {

            if ( isset($arrFiles[$objFiles->path]) || !file_exists(\System::getContainer()->getParameter('kernel.project_dir') . '/' . $objFiles->path)) {
                continue;
            }

            if ($objFiles->type == 'file') {

                $objFile = new \File($objFiles->path);
                if (!\in_array($objFile->extension, $allowedDownload) || preg_match('/^meta(_[a-z]{2})?\.txt$/', $objFile->basename)) {
                    continue;
                }

                $arrMeta = \Frontend::getMetaData($objFiles->meta, $objContainer->get('request_stack')->getCurrentRequest()->getLocale());
                if ($arrMeta['title'] == '') {
                    $arrMeta['title'] = \StringUtil::specialchars($objFiles->basename);
                }

                $strHref = \Environment::get('request');
                if (isset($_GET['file'])) {
                    $strHref = preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $strHref);
                }
                if (isset($_GET['cid'])) {
                    $strHref = preg_replace('/(&(amp;)?|\?)cid=\d+/', '', $strHref);
                }
                $strHref .= (strpos($strHref, '?') !== false ? '&amp;' : '?') . 'file=' . \System::urlEncode($objFiles->path);

                $arrFiles[$objFiles->path] = [
                    'id' => $objFiles->id,
                    'uuid' => \StringUtil::binToUuid($objFiles->uuid),
                    'name' => $objFile->basename,
                    'title' => \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $objFile->basename)),
                    'link' => $arrMeta['title'],
                    'caption' => $arrMeta['caption'],
                    'href' => $strHref,
                    'icon' => \Image::getPath($objFile->icon),
                    'mime' => $objFile->mime,
                    'meta' => $arrMeta,
                    'extension' => $objFile->extension,
                    'path' => $objFile->dirname,
                    'urlpath' => $objFile->path,
                    'filesize'  => \Controller::getReadableSize($objFile->filesize)
                ];
            }
            else {

                $objSubfiles = \FilesModel::findByPid($objFiles->uuid, ['order' => 'name']);
                if ($objSubfiles === null) {
                    continue;
                }

                while ($objSubfiles->next()) {
                    if ($objSubfiles->type == 'folder') {
                        continue;
                    }

                    $objFile = new \File($objSubfiles->path);
                    if (!\in_array($objFile->extension, $allowedDownload) || preg_match('/^meta(_[a-z]{2})?\.txt$/', $objFile->basename)) {
                        continue;
                    }

                    $arrMeta = \Frontend::getMetaData($objSubfiles->meta, $objContainer->get('request_stack')->getCurrentRequest()->getLocale());
                    if ($arrMeta['title'] == '') {
                        $arrMeta['title'] = \StringUtil::specialchars($objFile->basename);
                    }

                    $strHref = \Environment::get('request');
                    if (preg_match('/(&(amp;)?|\?)file=/', $strHref)) {
                        $strHref = preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $strHref);
                    }
                    $strHref .= (strpos($strHref, '?') !== false ? '&amp;' : '?') . 'file=' . \System::urlEncode($objSubfiles->path);

                    $arrFiles[$objSubfiles->path] = [
                        'id' => $objSubfiles->id,
                        'uuid' => $objSubfiles->uuid,
                        'name' => $objFile->basename,
                        'title' => \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $objFile->basename)),
                        'link' => $arrMeta['title'],
                        'caption' => $arrMeta['caption'],
                        'href' => $strHref,
                        'filesize' => \Controller::getReadableSize($objFile->filesize),
                        'icon' => \Image::getPath($objFile->icon),
                        'mime' => $objFile->mime,
                        'meta' => $arrMeta,
                        'extension' => $objFile->extension,
                        'path' => $objFile->dirname
                    ];
                }
            }
        }

        return $arrFiles;
    }
}