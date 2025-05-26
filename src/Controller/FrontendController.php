<?php

namespace Alnv\ContaoCatalogManagerBundle\Controller;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/catalog-manager', name: 'catalog-manager-controller', defaults: ['_scope' => 'frontend', '_token_check' => false])]
class FrontendController extends \Contao\CoreBundle\Controller\AbstractController
{

    #[Route(path: '/watchlist/update', methods: ["POST"])]
    public function updateWatchlist()
    {

        $this->container->get('contao.framework')->initialize();

        $arrData = explode(':', base64_decode(\StringUtil::decodeEntities(\Input::post('data'))));
        list($strIdentifier, $strTable) = $arrData;

        return new JsonResponse(\Alnv\ContaoCatalogManagerBundle\Library\Watchlist::updateWatchlist($strIdentifier, $strTable, \Input::post('items')));
    }

    #[Route(path: '/view-listing/{module}/{page}', methods: ["POST", "GET"])]
    public function getViewListing($module, $page)
    {

        global $objPage;

        $objPage = \PageModel::findByPK($page)->loadDetails();
        $GLOBALS['TL_LANGUAGE'] = $objPage->language;

        if (isset($_POST['requestUrl'])) {
            \Environment::set('request', (\Input::post('requestUrl') ?: ''));
        }

        (new \Alnv\ContaoCatalogManagerBundle\Hooks\PageLayout())->getMasterByPageId($page, \Input::get('item'));

        $objPage->ajaxContext = true;
        $strListing = \Controller::getFrontendModule($module);
        $strListing = \Controller::replaceInsertTags($strListing);

        return new JsonResponse([
            'template' => Toolkit::compress($strListing),
            'limit' => \Cache::get('limit_' . $module),
            'max' => (bool) \Cache::get('max_' . $module)
        ]);
    }

    #[Route(path: '/json-listing/{module}/{page}', methods: ["POST", "GET"])]
    public function getJsonListing($module, $page)
    {

        global $objPage;

        $objPage = \PageModel::findByPK($page)->loadDetails();
        $GLOBALS['TL_LANGUAGE'] = $objPage->language;

        (new \Alnv\ContaoCatalogManagerBundle\Hooks\PageLayout())->getMasterByPageId($page, \Input::get('item'));

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

        $objListing = new \Alnv\ContaoCatalogManagerBundle\Views\Listing($objModule->getTable(), $arrOptions);

        return new JsonResponse([
            'results' => $objListing->parse(),
            'pagination' => $objListing->getPagination(),
            'limit' => \Cache::get('limit_' . $module),
            'max' => (bool) \Cache::get('max_' . $module)
        ]);
    }

    #[Route(path: '/view-map/{module}/{page}', methods: ["GET"])]
    public function getViewMap($module, $page)
    {

        global $objPage;
        $objPage = \PageModel::findByPK($page)->loadDetails();
        (new \Alnv\ContaoCatalogManagerBundle\Hooks\PageLayout())->getMasterByPageId($page, \Input::get('item'));
        $objPage->ajaxContext = true;
        $strListing = \Controller::getFrontendModule($module);
        return new JsonResponse(['locations' => $strListing]);
    }

    #[Route(path: '/async-image', methods: ["POST", "GET"])]
    public function getAsyncImage()
    {

        $this->container->get('contao.framework')->initialize();
        $arrReturn = [
            'src' => null,
            'alt' => ''
        ];
        $objEntity = new \Alnv\ContaoCatalogManagerBundle\Views\Master(\Input::post('table'), [
            'alias' => \Input::post('id'),
            'id' => '1'
        ]);
        $objRoleResolver = \Alnv\ContaoCatalogManagerBundle\Library\RoleResolver::getInstance(\Input::post('table'), $objEntity->parse()[0]);
        $arrImage = $objRoleResolver->getValueByRole(\Input::post('role'));
        if (is_array($arrImage) && !empty($arrImage)) {
            $arrReturn['src'] = $arrImage[0]['img']['srcset'] ?? '';
            $arrReturn['alt'] = $arrImage[0]['alt'] ?? '';
        }
        return new JsonResponse($arrReturn);
    }

    #[Route(path: '/icalendar', methods: ["GET"])]
    public function getICalendar()
    {

        $this->container->get('contao.framework')->initialize();
        global $objPage;
        $objPage = \PageModel::findByPK(\Input::get('p'))->loadDetails();
        $objEntity = new \Alnv\ContaoCatalogManagerBundle\Views\Master(\Input::get('t'), [
            'alias' => \Input::get('i'),
            'id' => '1'
        ]);
        $arrMaster = $objEntity->parse()[0] ?: [];
        $objICalendar = new \Alnv\ContaoCatalogManagerBundle\Library\ICalendar($arrMaster);
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