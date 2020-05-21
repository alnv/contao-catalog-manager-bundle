<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

class Image {

    public static function getImage($strUuid, $intSize = null, &$arrImages=[], $arrOrderField=[]) {

        $objContainer = \System::getContainer();
        $arrUuids = \StringUtil::deserialize($strUuid, true);
        foreach ($arrUuids as $strUuid) {

            if (!\Validator::isUuid($strUuid)) {
                continue;
            }

            $objFile = \FilesModel::findByUuid($strUuid);
            if ($objFile == null) {
                continue;
            }

            if ($objFile->type == 'folder') {
                $objFiles = \FilesModel::findByPid($objFile->uuid);
                if ($objFiles !== null) {
                    while ($objFiles->next()) {
                        self::getImage(\StringUtil::binToUuid($objFiles->uuid),$intSize,$arrImages);
                    }
                }
                continue;
            }

            if (!file_exists($objContainer->getParameter('kernel.project_dir') . '/' . $objFile->path)) {
                continue;
            }

            $arrMeta = [];
            if ($objFile->meta) {
                $arrMeta = \Frontend::getMetaData($objFile->meta, $objContainer->get('request_stack')->getCurrentRequest()->getLocale());
            }

            $strStaticUrl = $objContainer->get('contao.assets.files_context')->getStaticUrl();
            $objPicture = $objContainer->get('contao.image.picture_factory')->create($objContainer->getParameter('kernel.project_dir') . '/' . $objFile->path, ($intSize ? (int) $intSize : null));
            $arrPicture = [
                'path' => $objFile->path,
                'uuid' => \StringUtil::binToUuid($objFile->uuid),
                'img' => $objPicture->getImg($objContainer->getParameter('kernel.project_dir'), $strStaticUrl),
                'sources' => $objPicture->getSources($objContainer->getParameter('kernel.project_dir'), $strStaticUrl),
            ];

            if ( !empty( $arrMeta ) ) {
                foreach ( $arrMeta as $strField => $strLabel ) {
                    if ($strField === 'link') {
                        $strLabel = \Controller::replaceInsertTags( $strLabel );
                    }
                    $arrPicture[$strField] = $strLabel;
                }
            }
            $arrImages[] = $arrPicture;
        }

        if (!empty($arrOrderField)) {
            $arrOrder = array_map( function () {}, array_flip($arrOrderField));
            foreach ($arrImages as $strKey => $arrValue) {
                if (array_key_exists($arrValue['uuid'], $arrOrder)) {
                    $arrOrder[$arrValue['uuid']] = $arrValue;
                    unset($arrImages[$strKey]);
                }
            }
            if (!empty( $arrImages)) {
                $arrOrder = array_merge($arrOrder, array_values($arrImages));
            }
            $arrImages = array_values(array_filter($arrOrder));
            unset($arrOrder);
        }

        return $arrImages;
    }

    public static function getUuids($strUuid) {

        $arrReturn = [];
        $arrUuids = \StringUtil::deserialize($strUuid, true);
        foreach ( $arrUuids as $strUuid ) {
            if (\Validator::isBinaryUuid($strUuid)) {
                $strUuid = \StringUtil::binToUuid($strUuid);
            }
            $arrReturn[] = $strUuid;
        }
        return $arrReturn;
    }
}