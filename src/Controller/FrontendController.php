<?php

namespace Alnv\ContaoCatalogManagerBundle\Controller;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * @Route("/view-listing/{module}/{page}", name="view-listing")
     * @Method({"POST"})
     */
    public function getViewListing($module, $page) {
        global $objPage;
        $objPage = \PageModel::findByPK($page)->loadDetails();
        (new \Alnv\ContaoCatalogManagerBundle\Hooks\PageLayout())->getMasterByPageId($page,\Input::get('item'));
        $objPage->ajaxContext = true;
        $strListing = \Controller::getFrontendModule($module);
        $strListing = \Controller::replaceInsertTags($strListing);
        return new JsonResponse(['template' => Toolkit::compress($strListing), 'limit' => \Cache::get('limit_' . $module), 'max' => (\Cache::get('max_' . $module) ? true : false)]);
    }

    /**
     *
     * @Route("/json-listing/{module}/{page}", name="json-listing")
     * @Method({"POST"})
     */
    public function getJsonListing($module, $page) {

        global $objPage;
        $objPage = \PageModel::findByPK($page)->loadDetails();
        (new \Alnv\ContaoCatalogManagerBundle\Hooks\PageLayout())->getMasterByPageId($page,\Input::get('item'));
        $objPage->ajaxContext = true;
        $objModule = \ModuleModel::findByPk($module);

        if ($objModule === null) {
            return new JsonResponse([]);
        }

        $strClass = \Module::findClass($objModule->type);
        if (!class_exists($strClass)) {
            return new JsonResponse([]);
        }

        $objModule = new $strClass($objModule);
        $objModule->setOptions();
        $arrOptions = $objModule->getOptions();
        $arrOptions['id'] = $module;
        return new JsonResponse(['results'=>(new \Alnv\ContaoCatalogManagerBundle\Views\Listing($objModule->getTable(), $arrOptions))->parse()]);
    }

    /**
     *
     * @Route("/view-map/{module}/{page}", name="view-map")
     * @Method({"GET"})
     */
    public function getViewMap($module, $page) {
        global $objPage;
        $objPage = \PageModel::findByPK( $page )->loadDetails();
        (new \Alnv\ContaoCatalogManagerBundle\Hooks\PageLayout())->getMasterByPageId($page,\Input::get('item'));
        $objPage->ajaxContext = true;
        $strListing = \Controller::getFrontendModule($module);
        return new JsonResponse(['locations' => $strListing]);
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
        $objRoleResolver = \Alnv\ContaoCatalogManagerBundle\Library\RoleResolver::getInstance(\Input::post('table'), $objEntity->parse()[0]);
        $arrImage = $objRoleResolver->getValueByRole(\Input::post('role'));
        if ( is_array( $arrImage ) && !empty( $arrImage ) ) {
            $arrReturn['src'] = $arrImage[0]['img']['srcset'];
            $arrReturn['alt'] = $arrImage[0]['alt'];
        }
        return new JsonResponse($arrReturn);
    }

    /**
     *
     * @Route("/icalendar", name="getICalendar")
     * @Method({"GET"})
     */
    public function getICalendar() {
        $this->container->get( 'contao.framework' )->initialize();
        global $objPage;
        $objPage = \PageModel::findByPK( \Input::get('p') )->loadDetails();
        $objEntity = new \Alnv\ContaoCatalogManagerBundle\Views\Master( \Input::get('t'), [
            'alias' => \Input::get('i'),
            'id' => '1'
        ]);
        $arrMaster = $objEntity->parse()[0] ?: [];
        $objICalendar = new \Alnv\ContaoCatalogManagerBundle\Library\ICalendar( $arrMaster );
        return new Response(
            $objICalendar->getICalFile(),
            200,
            [
                'Content-Type' => 'text/calendar;charset=utf-8',
                'Content-Disposition' => 'attachment;filename="'. $arrMaster['roleResolver']()->getValueByRole('alias') .'.ics"'
            ]
        );
    }
}