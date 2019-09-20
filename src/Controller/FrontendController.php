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
     * @Route("/listing/{id}", name="listing")
     * @Method({"GET"})
     */
    public function getListing( $id ) {

        $strListing = \Controller::getFrontendModule( $id );
        $strListing = \Controller::replaceInsertTags( $strListing );

        header('Content-Type: application/json');
        echo json_encode( [ 'template' => $strListing ], 512 );
        exit;
    }
}