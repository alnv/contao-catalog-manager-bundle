<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

class Image {

    public static function getImage( $strUuid, $intSize = null ) {

        $arrImages = [];
        $objContainer = \System::getContainer();
        $arrUuids = \StringUtil::deserialize( $strUuid, true );

        foreach ( $arrUuids as $strUuid ) {

            if ( !\Validator::isUuid( $strUuid ) ) {
                continue;
            }

            $objFile = \FilesModel::findByUuid( $strUuid );
            if ( $objFile == null ) {
                continue;
            }

            if ( !file_exists( $objContainer->getParameter('kernel.project_dir') . '/' . $objFile->path ) ) {
                continue;
            }

            $arrMeta = [];
            if ( $objFile->meta ) {
                $arrMeta = \Frontend::getMetaData( $objFile->meta, $objContainer->get('request_stack')->getCurrentRequest()->getLocale() );
            }

            $strStaticUrl = $objContainer->get('contao.assets.files_context')->getStaticUrl();
            $objPicture = $objContainer->get('contao.image.picture_factory')->create($objContainer->getParameter('kernel.project_dir') . '/' . $objFile->path, ( $intSize ? (int) $intSize : null ));
            $arrPicture = [
                'path' => $objFile->path,
                'img' => $objPicture->getImg( $objContainer->getParameter('kernel.project_dir'), $strStaticUrl ),
                'sources' => $objPicture->getSources( $objContainer->getParameter('kernel.project_dir'), $strStaticUrl ),
            ];

            if ( !empty( $arrMeta ) ) {
                foreach ( $arrMeta as $strField => $strLabel ) {
                    if ( $strField === 'link' ) {
                        $strLabel = \Controller::replaceInsertTags( $strLabel );
                    }
                    $arrPicture[ $strField ] = $strLabel;
                }
            }
            $arrImages[] = $arrPicture;
        }
        return $arrImages;
    }
}