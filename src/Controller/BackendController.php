<?php

namespace Alnv\ContaoCatalogManagerBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;


/**
 *
 * @Route("/contao/catalog", defaults={"_scope" = "backend", "_token_check" = false})
 */
class BackendController {


    /**
     *
     * @Route("/fields/{table}", name="catalog-fields")
     * @Method({"GET"})
     */
    public function getFields( $table ) {

        header('Content-Type: application/json');
        echo json_encode( [ 'name' => $table ], 512 );
        exit;
    }
}