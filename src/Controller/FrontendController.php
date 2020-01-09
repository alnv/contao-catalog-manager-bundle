<?php

namespace Alnv\ContaoCatalogManagerBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;


/**
 *
 * @Route("/catalog-manager", defaults={"_scope" = "frontend", "_token_check" = false})
 */
class FrontendController extends Controller {


    /**
     *
     * @Route("/listing/{module}/{page}", name="listing")
     * @Method({"GET"})
     */
    public function getListing( $module, $page ) {

        global $objPage;

        $objPage = \PageModel::findByPK( $page )->loadDetails();
        $objPage->ajaxContext = true;

        $strListing = \Controller::getFrontendModule( $module );
        $strListing = \Controller::replaceInsertTags( $strListing );

        header('Content-Type: application/json');
        echo json_encode( [ 'template' => $strListing ], 512 );
        exit;
    }


    /**
     *
     * @Route("/async-image", name="async-image")
     * @Method({"POST"})
     */
    public function getAsyncImage() {

        $this->container->get( 'contao.framework' )->initialize();

        $arrReturn = [
            'src' => null,
            'alt' => ''
        ];

        $objEntity = new \Alnv\ContaoCatalogManagerBundle\Views\Master( \Input::post('table'), [
            'alias' => \Input::post('id'),
            'id' => '1'
        ]);

        $objRoleResolver = \Alnv\ContaoCatalogManagerBundle\Library\RoleResolver::getInstance( \Input::post('table'), $objEntity->parse()[0] );
        $arrImage = $objRoleResolver->getValueByRole(\Input::post('role'));

        if ( is_array( $arrImage ) && !empty( $arrImage ) ) {

            $arrReturn['src'] = $arrImage[0]['img']['srcset'];
            $arrReturn['alt'] = $arrImage[0]['alt'];
            $arrReturn['test'] = $objEntity->parse()[0];
        }

        header('Content-Type: application/json');
        echo json_encode( $arrReturn, 512 );
        exit;
    }
}