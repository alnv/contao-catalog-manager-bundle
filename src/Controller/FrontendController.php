<?php

namespace Alnv\ContaoCatalogManagerBundle\Controller;

use Alnv\ContaoCatalogManagerBundle\EventListener\GetPageLayoutListener;
use Alnv\ContaoCatalogManagerBundle\Helper\Cache;
use Alnv\ContaoCatalogManagerBundle\Helper\CatalogTableGuard;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Library\ICalendar;
use Alnv\ContaoCatalogManagerBundle\Library\Watchlist;
use Alnv\ContaoCatalogManagerBundle\Models\WatchlistModel;
use Alnv\ContaoCatalogManagerBundle\Views\Listing;
use Alnv\ContaoCatalogManagerBundle\Views\Master;
use Contao\Controller;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\Database;
use Contao\Environment;
use Contao\Input;
use Contao\Module;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @deprecated Deprecated since Catalog-Manager 4.1, to be removed in 5.0.
 *             Die Endpunkte laufen ohne CSRF-Prüfung (_token_check=false) und ohne
 *             Seiten-/Modulrechteprüfung. Optionen anlegen/löschen wurde bereits in den
 *             BackendOptionController verschoben; die übrigen Endpunkte werden ersetzt.
 */
#[Route(path: 'catalog-manager', name: 'catalog-manager-frontend-controller', defaults: ['_scope' => 'frontend', '_token_check' => false])]
class FrontendController extends AbstractController
{
    private const WATCHLIST_MAX_ITEMS = 200;

    public function __construct(private readonly ?RateLimiterFactory $watchlistLimiter = null)
    {
    }

    private function triggerDeprecation(string $strMethod): void
    {
        \trigger_deprecation('alnv/catalog-manager-bundle', '4.1', 'Using "%s::%s()" (route "/catalog-manager/...") is deprecated and will be removed in 5.0.', self::class, $strMethod);
    }

    /**
     * Lädt eine veröffentlichte Seite oder wirft 404 (vorher: findByPK(...)->loadDetails() → 500 bei ungültiger ID).
     */
    private function loadPublishedPage(mixed $varPage): PageModel
    {
        $intPage = (int)$varPage;
        $objPage = $intPage > 0 ? PageModel::findPublishedById($intPage) : null;

        if ($objPage === null) {
            throw $this->createNotFoundException('Page not found.');
        }

        return $objPage->loadDetails();
    }

    #[Route(path: '/watchlist/update', methods: ["POST"], defaults: ['_token_check' => true])]
    public function updateWatchlist(Request $request): JsonResponse
    {
        $this->triggerDeprecation(__FUNCTION__);
        $this->container->get('contao.framework')->initialize();

        if ($this->watchlistLimiter !== null) {
            $objLimit = $this->watchlistLimiter->create('watchlist-' . ($request->getClientIp() ?? 'unknown'))->consume();

            if (!$objLimit->isAccepted()) {
                return new JsonResponse(['error' => 'Too many requests.'], 429, [
                    'Retry-After' => (string)max(1, $objLimit->getRetryAfter()->getTimestamp() - time()),
                ]);
            }
        }

        $strDecoded = base64_decode(StringUtil::decodeEntities((string)Input::post('data')), true);
        $arrData = explode(':', (string)$strDecoded, 2);
        $strIdentifier = (string)($arrData[0] ?? '');
        $strTable = (string)($arrData[1] ?? '');

        // Nur registrierte Katalog-Tabellen und numerische, existierende IDs zulassen
        if (!ctype_digit($strIdentifier) || (int)$strIdentifier < 1 || !CatalogTableGuard::isCatalogTable($strTable)) {
            return new JsonResponse(['error' => 'Invalid watchlist item.'], 400);
        }

        $objEntity = Database::getInstance()
            ->prepare('SELECT id FROM ' . Database::quoteIdentifier($strTable) . ' WHERE id=?')
            ->limit(1)
            ->execute((int)$strIdentifier);

        if ($objEntity->numRows < 1) {
            return new JsonResponse(['error' => 'Invalid watchlist item.'], 404);
        }

        $intUnits = max(0, min(9999, (int)Input::post('items')));

        // Neue Einträge nur bis zur Obergrenze pro Session (Schutz vor Tabellen-Spam)
        if ($intUnits > 0 && !WatchlistModel::getByIdentifierAndTable($strIdentifier, $strTable)) {
            $objOpen = WatchlistModel::getBySession();
            if ($objOpen !== null && $objOpen->count() >= self::WATCHLIST_MAX_ITEMS) {
                return new JsonResponse(['error' => 'Watchlist is full.'], 422);
            }
        }
        $arrResponse = Watchlist::updateWatchlist($strIdentifier, $strTable, $intUnits);
        $arrResponse['requestToken'] = $this->container->get('contao.csrf.token_manager')->getDefaultTokenValue();

        return new JsonResponse($arrResponse);
    }

    #[Route(path: '/view-listing/{module}/{page}', methods: ["POST"])]
    public function getViewListing($module, $page): JsonResponse
    {
        $this->triggerDeprecation(__FUNCTION__);

        $this->container->get('contao.framework')->initialize();

        global $objPage;

        $objPage = $this->loadPublishedPage($page);
        $GLOBALS['TL_LANGUAGE'] = $objPage->language;

        if (isset($_POST['requestUrl'])) {
            Environment::set('request', (Input::post('requestUrl') ?: ''));
        }

        (new GetPageLayoutListener())->getMasterByPageId($page, Input::get('item'));

        $objPage->ajaxContext = true;

        $strListing = Controller::getFrontendModule($module);
        $strListing = Toolkit::replaceInsertTags($strListing);

        return new JsonResponse([
            'template' => Toolkit::compress($strListing),
            'limit' => Cache::get('limit_' . $module),
            'max' => (bool)Cache::get('max_' . $module)
        ]);
    }

    #[Route(path: '/json-listing/{module}/{page}', methods: ["POST", "GET"])]
    public function getJsonListing($module, $page): JsonResponse
    {
        $this->triggerDeprecation(__FUNCTION__);

        $this->container->get('contao.framework')->initialize();

        global $objPage;

        $objPage = $this->loadPublishedPage($page);
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

        $objListing = new Listing($objModule->getTable(), $arrOptions);

        return new JsonResponse([
            'results' => $objListing->parse(),
            'pagination' => $objListing->getPagination(),
            'limit' => Cache::get('limit_' . $module),
            'max' => (bool)Cache::get('max_' . $module)
        ]);
    }

    #[Route(path: '/view-map/{module}/{page}', methods: ["GET"])]
    public function getViewMap($module, $page): JsonResponse
    {
        $this->triggerDeprecation(__FUNCTION__);

        global $objPage;

        $this->container->get('contao.framework')->initialize();
        $objPage = $this->loadPublishedPage($page);
        (new GetPageLayoutListener())->getMasterByPageId($page, Input::get('item'));

        $objPage->ajaxContext = true;
        $strListing = Controller::getFrontendModule($module);

        return new JsonResponse(['locations' => $strListing]);
    }

    #[Route(path: '/icalendar', methods: ["GET"])]
    public function getICalendar(): Response
    {
        $this->triggerDeprecation(__FUNCTION__);

        $this->container->get('contao.framework')->initialize();

        global $objPage;

        $strTable = (string)Input::get('t');
        $strItem = (string)Input::get('i');

        // Tabellenname stammt aus dem Request: nur registrierte Katalog-Tabellen zulassen
        if (!CatalogTableGuard::isCatalogTable($strTable) || $strItem === '') {
            throw $this->createNotFoundException();
        }

        $objPage = PageModel::findPublishedById((int)Input::get('p'));
        if ($objPage === null) {
            throw $this->createNotFoundException();
        }
        $objPage->loadDetails();

        $objEntity = new Master($strTable, [
            'alias' => $strItem,
            'id' => '1'
        ]);

        $arrMaster = $objEntity->parse()[0] ?? [];
        if (empty($arrMaster)) {
            throw $this->createNotFoundException();
        }

        $objICalendar = new ICalendar($arrMaster);

        $strFilename = (string)($arrMaster['roleResolver']()->getValueByRole('alias') ?: 'event') . '.ics';
        $strFallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $strFilename) ?: 'event.ics';

        return new Response(
            $objICalendar->getICalFile(),
            200,
            [
                'Content-Type' => 'text/calendar;charset=utf-8',
                'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $strFallback, $strFallback)
            ]
        );
    }
}
