<?php

namespace Alnv\ContaoCatalogManagerBundle\Controller;

use Alnv\ContaoCatalogManagerBundle\Helper\Cache;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\EventListener\GetPageLayoutListener;
use Alnv\ContaoCatalogManagerBundle\Library\ICalendar;
use Alnv\ContaoCatalogManagerBundle\Library\Watchlist;
use Alnv\ContaoCatalogManagerBundle\Views\Listing;
use Alnv\ContaoCatalogManagerBundle\Views\Master;
use Contao\Controller;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\Environment;
use Contao\Input;
use Contao\Module;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: 'catalog-manager', name: 'catalog-manager-frontend-controller', defaults: ['_scope' => 'frontend'])]
class FrontendController extends AbstractController
{

    #[Route(path: '/watchlist/update', methods: ["POST"])]
    public function updateWatchlist(): JsonResponse
    {

        $this->container->get('contao.framework')->initialize();

        $arrData = explode(':', base64_decode(StringUtil::decodeEntities(Input::post('data'))));
        list($strIdentifier, $strTable) = $arrData;

        return new JsonResponse(Watchlist::updateWatchlist($strIdentifier, $strTable, Input::post('items')));
    }

    #[Route(path: '/view-listing/{module}/{page}', methods: ["POST"])]
    public function getViewListing($module, $page): JsonResponse
    {

        global $objPage;

        $objPage = PageModel::findByPK($page)->loadDetails();
        $GLOBALS['TL_LANGUAGE'] = $objPage->language;

        if (isset($_POST['requestUrl'])) {
            Environment::set('request', (Input::post('requestUrl') ?: ''));
        }

        (new GetPageLayoutListener())->getMasterByPageId($page, Input::get('item'));

        $objPage->ajaxContext = true;

        $strListing = Controller::getFrontendModule($module);
        $strListing = Toolkit::replaceInsertTags($strListing);

        return new JsonResponse(['template' => Toolkit::compress($strListing), 'limit' => Cache::get('limit_' . $module), 'max' => (Cache::get('max_' . $module) ? true : false)]);
    }

    #[Route(path: '/json-listing/{module}/{page}', methods: ["POST"])]
    public function getJsonListing($module, $page): JsonResponse
    {

        global $objPage;

        $objPage = PageModel::findByPK($page)->loadDetails();
        $GLOBALS['TL_LANGUAGE'] = $objPage->language;

        (new GetPageLayoutListener())->getMasterByPageId($page, Input::get('item'));
        $objPage->ajaxContext = true;
        $objModule = ModuleModel::findByPk($module);

        if ($objModule === null) {
            return new JsonResponse([]);
        }

        $strClass = Module::findClass($objModule->type);
        if (!class_exists($strClass)) {
            return new JsonResponse([]);
        }

        $objModule = new $strClass($objModule);
        $objModule->setOptions();
        $arrOptions = $objModule->getOptions();
        $arrOptions['id'] = $module;

        return new JsonResponse(['results' => (new Listing($objModule->getTable(), $arrOptions))->parse()]);
    }

    #[Route(path: '/view-map/{module}/{page}', methods: ["GET"])]
    public function getViewMap($module, $page): JsonResponse
    {

        global $objPage;

        $objPage = PageModel::findByPK($page)->loadDetails();
        (new GetPageLayoutListener())->getMasterByPageId($page, Input::get('item'));

        $objPage->ajaxContext = true;
        $strListing = Controller::getFrontendModule($module);

        return new JsonResponse(['locations' => $strListing]);
    }

    #[Route(path: '/icalendar', methods: ["GET"])]
    public function getICalendar(): Response
    {

        $this->container->get('contao.framework')->initialize();

        global $objPage;

        $objPage = PageModel::findByPK(Input::get('p'))->loadDetails();
        $objEntity = new Master(Input::get('t'), [
            'alias' => Input::get('i'),
            'id' => '1'
        ]);

        $arrMaster = $objEntity->parse()[0] ?: [];
        $objICalendar = new ICalendar($arrMaster);

        return new Response(
            $objICalendar->getICalFile(),
            200,
            [
                'Content-Type' => 'text/calendar;charset=utf-8',
                'Content-Disposition' => 'attachment;filename="' . $arrMaster['roleResolver']()->getValueByRole('alias') . '.ics"'
            ]
        );
    }
}