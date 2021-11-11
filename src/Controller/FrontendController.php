<?php

namespace Alnv\ContaoCatalogManagerBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 *
 * @Route("/catalog-manager", defaults={"_scope" = "frontend", "_token_check" = false})
 */
class FrontendController extends \Contao\CoreBundle\Controller\AbstractController {

    /**
     *
     * @Route("/watchlist/update", methods={"POST"}, name="update-watchlist")
     */
    public function updateWatchlist() {

        $this->container->get('contao.framework')->initialize();

        $arrData = explode(':', base64_decode(\StringUtil::decodeEntities(\Input::post('data'))));
        list($strIdentifier, $strTable) = $arrData;

        return new JsonResponse(\Alnv\ContaoCatalogManagerBundle\Library\Watchlist::updateWatchlist($strIdentifier, $strTable, \Input::post('items')));
    }

    /**
     *
     * @Route("/view-listing/{module}/{page}", methods={"POST"}, name="view-listing")
     */
    public function getViewListing($module, $page) {

        global $objPage;
        $objPage = \PageModel::findByPK($page)->loadDetails();
        $GLOBALS['TL_LANGUAGE'] = $objPage->language;

        if (isset($_POST['requestUrl'])) {
            \Environment::set('request', (\Input::post('requestUrl')?:''));
        }

        (new \Alnv\ContaoCatalogManagerBundle\Hooks\PageLayout())->getMasterByPageId($page, \Input::get('item'));
        $objPage->ajaxContext = true;
        $strListing = \Controller::getFrontendModule($module);
        $strListing = \Controller::replaceInsertTags($strListing);
        return new JsonResponse(['template' => Toolkit::compress($strListing), 'limit' => \Cache::get('limit_' . $module), 'max' => (\Cache::get('max_' . $module) ? true : false)]);
    }

    /**
     *
     * @Route("/json-listing/{module}/{page}", methods={"POST"}, name="json-listing")
     */
    public function getJsonListing($module, $page) {

        global $objPage;
        $objPage = \PageModel::findByPK($page)->loadDetails();
        $GLOBALS['TL_LANGUAGE'] = $objPage->language;
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
     * @Route("/view-map/{module}/{page}", methods={"GET"}, name="view-map")
     */
    public function getViewMap($module, $page) {

        global $objPage;
        $objPage = \PageModel::findByPK($page)->loadDetails();
        (new \Alnv\ContaoCatalogManagerBundle\Hooks\PageLayout())->getMasterByPageId($page,\Input::get('item'));
        $objPage->ajaxContext = true;
        $strListing = \Controller::getFrontendModule($module);
        return new JsonResponse(['locations' => $strListing]);
    }

    /**
     *
     * @Route("/async-image", methods={"POST"}, name="async-image")
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
     * @Route("/icalendar", methods={"GET"}, name="getICalendar")
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