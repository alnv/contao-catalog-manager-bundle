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
class FrontendController {


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
}