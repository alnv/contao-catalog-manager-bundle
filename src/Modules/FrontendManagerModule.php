<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder;
use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\UploadFolderManager;
use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\UploadSettingsResolver;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\AccessDeniedException;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\AccessGuard;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\AjaxResponse;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\FieldPolicy;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\ManagerConfig;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\MemberContext;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\RecordService;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\TableProvider;
use Alnv\ContaoCatalogManagerBundle\Library\RoleResolver;
use Alnv\ContaoCatalogManagerBundle\Mcp\DcaRecordManager;
use Alnv\ContaoCatalogManagerBundle\Mcp\HeadlessDataContainer;
use Alnv\ContaoCatalogManagerBundle\Mcp\RecordStub;
use Alnv\ContaoCatalogManagerBundle\Views\Listing as ViewListing;
use Contao\BackendTemplate;
use Contao\Combiner;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;

class FrontendManagerModule extends Module
{
    public const TYPE = 'catalog-frontend-manager';

    public const AJAX_PARAM = '_cmfm_ajax';

    protected $strTemplate = 'mod_catalog_frontend_manager';

    protected ?ManagerConfig $config = null;

    protected ?AccessGuard $guard = null;

    protected ?RecordService $service = null;

    protected array $dca = [];

    protected bool $ajax = false;

    protected string $ajaxCode = AjaxResponse::CODE_OK;

    protected ?string $ajaxMessage = null;

    protected array $ajaxMessages = [];

    protected ?string $viewUrl = null;

    public function generate(): string
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### ' . strtoupper((string)($GLOBALS['TL_LANG']['FMD'][self::TYPE][0] ?? self::TYPE)) . ' ###';
            $template->title = $this->headline;
            $template->id = $this->id;
            $template->link = $this->name;
            $template->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', ['do' => 'themes', 'table' => 'tl_module', 'act' => 'edit', 'id' => $this->id]));

            return $template->parse();
        }

        if ($this->customTpl) {
            $this->strTemplate = $this->customTpl;
        }

        return parent::generate();
    }

    protected function compile(): void
    {
        System::loadLanguageFile('default');

        $this->ajax = $this->isAjaxRequest();
        $text = $this->getText();

        $this->initialize();

        if (!$this->ajax) {
            $this->addAssets();
        }

        $this->Template->text = $text;
        $this->Template->moduleId = (int)$this->id;
        $this->Template->formId = $this->getFormSubmitId();
        $this->Template->requestToken = $this->getRequestToken();
        $this->Template->clientConfig = $this->getClientConfig();
        $this->Template->view = 'list';
        $this->Template->messages = [];

        try {
            $this->guard->assertCanUse();
        } catch (AccessDeniedException $e) {
            $notAuthenticated = AccessDeniedException::NOT_AUTHENTICATED === $e->getLevel();
            $message = $notAuthenticated ? ($text['loginRequired'] ?? 'Please log in.') : ($text['accessDenied'] ?? 'Access denied.');

            if ($this->ajax) {
                $this->respond(AjaxResponse::classify($e), ['message' => $notAuthenticated ? ($text['sessionExpired'] ?? $message) : $message]);
            }

            $this->Template->view = 'denied';
            $this->Template->deniedMessage = $message;

            return;
        }

        if ($this->ajax) {
            $this->handleAjax();
        }

        $request = $this->getRequest();
        $messages = [];

        try {
            if ($request->isMethod('POST') && $this->isOwnSubmission() && \in_array(Input::post('_cmfm_action'), ['copy', 'delete', 'toggle'], true)) {
                $this->handleOperation((string)Input::post('_cmfm_action'), (int)Input::post('_cmfm_id'));
            }

            $this->renderView((string)Input::get('cmfm_act'), (int)Input::get('cmfm_id'));
        } catch (AccessDeniedException $e) {
            $this->logDenied($e);
            $messages[] = ['type' => 'error', 'text' => $text['recordDenied'] ?? 'The record does not exist or you are not allowed to access it.'];

            try {
                $this->renderList();
            } catch (AccessDeniedException $e) {
                $this->logDenied($e);
                $this->Template->view = 'denied';
                $this->Template->deniedMessage = $text['accessDenied'] ?? 'Access denied.';
            }
        }

        $this->Template->messages = array_merge($this->pullFlashMessages(), $messages, $this->Template->messages ?: []);
    }

    // ---------------------------------------------------------------
    // Initialisierung
    // ---------------------------------------------------------------

    protected function initialize(): void
    {
        $this->config = ManagerConfig::fromModuleRow($this->objModel ? $this->objModel->row() : $this->arrData);
        $tables = new TableProvider();
        $allowed = '' !== $this->config->table && $tables->isAllowed($this->config->table);
        $this->dca = $allowed ? $tables->loadDca($this->config->table) : [];

        $this->guard = new AccessGuard($this->config, FieldPolicy::fromGlobals($this->dca), MemberContext::fromSecurity(), $allowed);

        $container = System::getContainer();
        $persister = new DcaRecordManager(
            (string)$container->getParameter('kernel.project_dir'),
            (string)($GLOBALS['TL_LANGUAGE'] ?? 'de'),
            [$this->config->table],
        );

        // Events nach jeder Schreibaktion (FrontendManagerEvents), z. B. für Benachrichtigungen
        $this->service = new RecordService(
            $this->guard,
            $persister,
            dispatcher: $container->get('event_dispatcher'),
            moduleId: (int)$this->id,
            logger: $container->has('monolog.logger.contao.error') ? $container->get('monolog.logger.contao.error') : null,
        );
    }

    protected function addAssets(): void
    {
        $css = new Combiner();
        $css->add('bundles/alnvcontaocatalogmanager/css/frontend-manager.scss');
        $GLOBALS['TL_CSS']['cm-frontend-manager'] = $css->getCombinedFile();

        $js = new Combiner();
        $js->add('bundles/alnvcontaocatalogmanager/js/frontend-manager.js');
        $GLOBALS['TL_BODY']['cm-frontend-manager'] = '<script src="' . $js->getCombinedFile() . '" defer></script>';
    }

    protected function getClientConfig(): array
    {
        $text = $this->getText();
        $keys = ['loading', 'networkError', 'timeout', 'invalidResponse', 'sessionExpired', 'csrf', 'accessDenied',
            'recordDenied', 'formErrors', 'error', 'databaseError', 'badRequest', 'dismiss', 'busy'];

        return [
            'moduleId' => (int)$this->id,
            'param' => self::AJAX_PARAM,
            'token' => $this->getRequestToken(),
            'timeout' => 30000,
            'labels' => array_intersect_key(array_filter($text, 'is_string'), array_flip($keys)),
        ];
    }

    // ---------------------------------------------------------------
    // AJAX
    // ---------------------------------------------------------------

    protected function isAjaxRequest(): bool
    {
        $request = $this->getRequest();

        if (!$request->isXmlHttpRequest()) {
            return false;
        }

        $target = $request->query->get(self::AJAX_PARAM) ?? $request->request->get(self::AJAX_PARAM);

        return \is_scalar($target) && (string)$target === (string)(int)$this->id;
    }

    protected function handleAjax(): never
    {
        $request = $this->getRequest();
        $text = $this->getText();
        $assetsBefore = $this->snapshotAssets();

        try {
            if ($request->isMethod('POST')) {
                // Contao prüft das Token bei XHR-Anfragen nicht → hier für jede schreibende Anfrage
                if (!$this->isTokenValid()) {
                    $this->respond(AjaxResponse::CODE_CSRF, ['message' => $text['csrf'] ?? 'Your session has expired.']);
                }

                $action = (string)Input::post('_cmfm_action');
                $formSubmit = (string)Input::post('FORM_SUBMIT');

                if ($formSubmit === $this->getFormSubmitId() && \in_array($action, ['copy', 'delete', 'toggle'], true)) {
                    $result = $this->performOperation($action, (int)Input::post('_cmfm_id'));
                    $this->ajaxMessages[] = ['type' => 'confirm', 'text' => $result['message']];
                    $this->renderView((string)($result['next']['cmfm_act'] ?? ''), (int)($result['next']['cmfm_id'] ?? 0));
                } elseif ($formSubmit === $this->getFormSubmitId() . '_form' && 'save' === $action) {
                    // renderForm() erkennt die Übermittlung selbst (gleiche Prüfungen wie ohne JS)
                    $this->renderView((string)Input::get('cmfm_act'), (int)Input::get('cmfm_id'));
                } else {
                    $this->respond(AjaxResponse::CODE_BAD_REQUEST, ['message' => $text['badRequest'] ?? 'Invalid request.']);
                }
            } elseif ($request->isMethod('GET')) {
                $this->renderView((string)Input::get('cmfm_act'), (int)Input::get('cmfm_id'));
            } else {
                $this->respond(AjaxResponse::CODE_BAD_REQUEST, ['message' => $text['badRequest'] ?? 'Invalid request.']);
            }
        } catch (ResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->respondException($e);
        }

        $this->respondView($assetsBefore);
    }

    protected function respondException(\Throwable $e): never
    {
        $text = $this->getText();
        $code = AjaxResponse::classify($e);

        if ($e instanceof AccessDeniedException) {
            $this->logDenied($e);
        } elseif (AjaxResponse::CODE_VALIDATION !== $code) {
            $this->logError($e);
        }

        $message = match ($code) {
            AjaxResponse::CODE_NOT_AUTHENTICATED => $text['sessionExpired'] ?? 'Your session has expired. Please log in again.',
            AjaxResponse::CODE_NOT_FOUND => $text['recordDenied'] ?? 'The record does not exist or you are not allowed to access it.',
            AjaxResponse::CODE_FORBIDDEN => $text['accessDenied'] ?? 'Access denied.',
            AjaxResponse::CODE_VALIDATION => $this->sanitizeMessage($e->getMessage()),
            AjaxResponse::CODE_DATABASE => $text['databaseError'] ?? 'The data could not be saved. Please try again later.',
            default => $text['error'] ?? 'An unexpected error occurred.',
        };

        $this->respond($code, ['message' => $message]);
    }

    protected function respondView(array $assetsBefore): never
    {
        $template = new FrontendTemplate('cm_frontend_manager_view');
        $template->setData($this->Template->getData());

        $html = (string)System::getContainer()->get('contao.insert_tag.parser')->replaceInline($template->parse());
        $html = $this->stripAjaxParameter($html);

        $form = 'form' === $this->Template->view ? (array)$this->Template->form : [];

        $this->respond($this->ajaxCode, [
            'message' => $this->ajaxMessage,
            'messages' => $this->ajaxMessages,
            'view' => (string)$this->Template->view,
            'html' => $html,
            'url' => null !== $this->viewUrl ? $this->stripAjaxParameter($this->viewUrl) : null,
            'errors' => $form['errors'] ?? [],
            'record' => !empty($form['recordId']) ? ['id' => (int)$form['recordId']] : null,
            'assets' => $this->diffAssets($assetsBefore),
        ]);
    }

    protected function respond(string $code, array $payload = []): never
    {
        throw new ResponseException(AjaxResponse::create($code, $this->getRequestToken(), $payload));
    }

    protected function snapshotAssets(): array
    {
        $snapshot = [];

        foreach (['TL_CSS', 'TL_JAVASCRIPT', 'TL_HEAD', 'TL_BODY'] as $key) {
            $snapshot[$key] = \is_array($GLOBALS[$key] ?? null) ? $GLOBALS[$key] : [];
        }

        return $snapshot;
    }

    /**
     * @return array{css: list<string>, js: list<string>, html: list<string>}
     */
    protected function diffAssets(array $before): array
    {
        $now = $this->snapshotAssets();
        $assets = ['css' => [], 'js' => [], 'html' => []];
        $map = ['TL_CSS' => 'css', 'TL_JAVASCRIPT' => 'js', 'TL_HEAD' => 'html', 'TL_BODY' => 'html'];

        foreach ($map as $global => $type) {
            foreach ($now[$global] as $entry) {
                if (\is_string($entry) && !\in_array($entry, $before[$global], true)) {
                    // TL_CSS/TL_JAVASCRIPT: "pfad|static|media" → nur der Pfad
                    $assets[$type][] = 'html' === $type ? $entry : explode('|', $entry)[0];
                }
            }
        }

        // Relative Pfade auf die Basis-URL beziehen
        $base = rtrim($this->getRequest()->getBasePath(), '/') . '/';

        foreach (['css', 'js'] as $type) {
            $assets[$type] = array_values(array_unique(array_map(
                static fn (string $url) => preg_match('#^(https?:)?//|^/#', $url) ? $url : $base . ltrim($url, '/'),
                $assets[$type]
            )));
        }

        $assets['html'] = array_values(array_unique($assets['html']));

        return $assets;
    }

    public static function stripAjaxParameter(string $html): string
    {
        return (string)preg_replace_callback(
            '/(\?|&amp;|&)' . preg_quote(self::AJAX_PARAM, '/') . '=[^&"\'#\s<>]*(&amp;|&)?/',
            static fn (array $m): string => isset($m[2]) && '' !== $m[2] ? ('?' === $m[1] ? '?' : $m[1]) : '',
            $html
        );
    }

    // ---------------------------------------------------------------
    // Operatoren: Duplizieren, Löschen, Sichtbarkeit
    // ---------------------------------------------------------------

    protected function performOperation(string $operation, int $id): array
    {
        $text = $this->getText();

        switch ($operation) {
            case 'copy':
                $newId = $this->service->copy($id);

                return [
                    'message' => $text['copied'] ?? 'The record has been duplicated.',
                    'next' => $this->guard->canPerform(ManagerConfig::OPERATION_EDIT) ? ['cmfm_act' => 'edit', 'cmfm_id' => $newId] : [],
                ];

            case 'delete':
                $this->service->delete($id);

                return ['message' => $text['deleted'] ?? 'The record has been deleted.', 'next' => []];

            case 'toggle':
                $visible = $this->service->toggle($id);

                return [
                    'message' => $visible ? ($text['published'] ?? 'The record is now visible.') : ($text['unpublished'] ?? 'The record is now hidden.'),
                    'next' => [],
                ];
        }

        throw new AccessDeniedException(AccessDeniedException::OPERATION, $operation);
    }

    protected function handleOperation(string $operation, int $id): never
    {
        $result = $this->runSafely(fn () => $this->performOperation($operation, $id));

        if (null !== $result) {
            $this->addFlash('confirm', $result['message']);
            $this->redirectTo($result['next']);
        }

        $this->redirectTo([]);
    }

    protected function runSafely(callable $action): mixed
    {
        $text = $this->getText();

        try {
            return $action();
        } catch (AccessDeniedException $e) {
            $this->logDenied($e);
            $this->addFlash('error', $text['recordDenied'] ?? 'Action not allowed.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $this->sanitizeMessage($e->getMessage()));
        } catch (\Throwable $e) {
            $this->logError($e);
            $this->addFlash('error', AjaxResponse::CODE_DATABASE === AjaxResponse::classify($e) ? ($text['databaseError'] ?? 'Database error.') : ($text['error'] ?? 'An unexpected error occurred.'));
        }

        return null;
    }

    // ---------------------------------------------------------------
    // Ansichten
    // ---------------------------------------------------------------

    protected function renderView(string $action, int $id): void
    {
        switch ($action) {
            case 'edit':
                $this->renderForm($id);
                break;

            case 'create':
                $this->renderForm(null);
                break;

            case 'delete':
                $this->renderDeleteConfirmation($id);
                break;

            default:
                $this->renderList();
        }
    }

    protected function renderList(): void
    {
        $text = $this->getText();
        $filterInput = Input::get('cmfm_f');
        $filterInput = \is_array($filterInput) ? $filterInput : [];

        $list = $this->service->getListOptions($filterInput, Input::get('cmfm_sort') ?: null, Input::get('cmfm_dir') ?: null);
        $listing = new ViewListing($this->config->table, $list['options']);
        $entities = $listing->parse();

        $columns = [];
        foreach ($this->guard->getListFields() as $field) {
            $columns[] = [
                'name' => $field,
                'label' => $this->guard->getPolicy()->getLabel($field),
                'sortable' => \in_array($field, $this->guard->getSortableFields(), true),
                'sorted' => $list['sort'] === $field ? strtolower($list['direction']) : null,
                'sortUrl' => $this->buildUrl([
                    'cmfm_sort' => $field,
                    'cmfm_dir' => ($list['sort'] === $field && 'ASC' === $list['direction']) ? 'desc' : 'asc',
                ], true),
            ];
        }

        $rows = [];
        foreach ($entities as $entity) {
            $id = (int)($entity['id'] ?? $entity['origin']['id'] ?? 0);
            $raw = array_merge($entity, $entity['origin'] ?? []);
            $cells = [];

            foreach ($columns as $column) {
                $cells[$column['name']] = $this->toDisplayString($entity[$column['name']] ?? '');
            }

            $rows[] = [
                'id' => $id,
                'cells' => $cells,
                'title' => ($cells[$columns[0]['name'] ?? ''] ?? '') ?: ('#' . $id),
                'visible' => $this->service->isVisible($raw),
                'editUrl' => $this->buildUrl(['cmfm_act' => 'edit', 'cmfm_id' => $id]),
                'deleteUrl' => $this->buildUrl(['cmfm_act' => 'delete', 'cmfm_id' => $id]),
            ];
        }

        $this->Template->view = 'list';
        $this->Template->columns = $columns;
        $this->Template->rows = $rows;
        $this->Template->total = $listing->countRows();
        $this->Template->pagination = $listing->getPagination();
        $this->Template->filters = $this->buildFilters($list['activeFilters']);
        $this->Template->filterResetUrl = $this->buildUrl([]);
        $this->Template->actionUrl = $this->buildUrl([], true);
        $this->Template->createUrl = $this->buildUrl(['cmfm_act' => 'create']);
        $this->Template->can = [
            'create' => $this->guard->canPerform(ManagerConfig::OPERATION_CREATE),
            'edit' => $this->guard->canPerform(ManagerConfig::OPERATION_EDIT),
            'copy' => $this->guard->canPerform(ManagerConfig::OPERATION_COPY),
            'delete' => $this->guard->canPerform(ManagerConfig::OPERATION_DELETE),
            'toggle' => $this->guard->canPerform(ManagerConfig::OPERATION_TOGGLE),
        ];
        $this->Template->emptyText = $text['empty'] ?? 'No records found.';
        $this->viewUrl = $this->buildUrl([], true);
    }

    protected function renderForm(?int $id, bool $acceptSubmit = true): void
    {
        $text = $this->getText();

        if (null === $id) {
            $this->guard->assertOperation(ManagerConfig::OPERATION_CREATE);
            $record = $this->getDefaultValues();
        } else {
            $record = $this->service->loadForEdit($id);
        }

        $formId = $this->getFormSubmitId() . '_form';
        $builder = new DcFormBuilder(
            $this->config->table,
            $this->dca,
            $this->guard->getEditableFields(),
            $this->guard->getReadonlyFields(),
            [
                'formId' => $formId,
                'recordId' => $id,
                // Upload-Einstellungen aus der DCA des Feldes, sonst global; Ziel ggf. Home-Verzeichnis
                'uploadResolver' => UploadSettingsResolver::fromContao($this->config->useHomeDir),
                'uploadFolders' => UploadFolderManager::fromContao(),
                'member' => $this->guard->getMember(),
            ]
        );

        $submitted = $acceptSubmit
            && $this->getRequest()->isMethod('POST')
            && Input::post('FORM_SUBMIT') === $formId
            && 'save' === Input::post('_cmfm_action')
            && (int)Input::post('_cmfm_id') === (int)$id
            && $this->isTokenValid();

        $result = $builder->build($record, $submitted);
        $formError = null;

        if ($result->isValid()) {
            $savedId = $this->runFormSave($id, $result->values, $formError);

            if (null !== $savedId) {
                $builder->finish();
                $message = $text['saved'] ?? 'The record has been saved.';
                $showList = null !== Input::post('_cmfm_close') || !$this->guard->canPerform(ManagerConfig::OPERATION_EDIT);

                if ($this->ajax) {
                    $this->ajaxMessages[] = ['type' => 'confirm', 'text' => $message];
                    $showList ? $this->renderList() : $this->renderForm($savedId, false);

                    return;
                }

                $this->addFlash('confirm', $message);
                $this->redirectTo($showList ? [] : ['cmfm_act' => 'edit', 'cmfm_id' => $savedId]);
            }
        }

        $params = null === $id ? ['cmfm_act' => 'create'] : ['cmfm_act' => 'edit', 'cmfm_id' => $id];

        $this->Template->view = 'form';
        $this->Template->form = [
            'id' => $formId,
            'recordId' => (int)$id,
            'isNew' => null === $id,
            'groups' => $result->groups,
            'errors' => $result->errors,
            'formError' => $formError,
            'hasSelectors' => $result->hasSelectors,
            'action' => $this->buildUrl($params),
            'cancelUrl' => $this->buildUrl([]),
            'reloadField' => DcFormBuilder::RELOAD_FIELD,
            'headline' => null === $id ? ($text['createHeadline'] ?? 'New record') : $this->getEditHeadline($id, $record),
        ];
        $this->viewUrl = $this->buildUrl($params);

        if ($submitted && ($result->hasErrors() || null !== $formError)) {
            $this->ajaxCode = AjaxResponse::CODE_VALIDATION;
            $this->ajaxMessage = $formError ?? ($text['formErrors'] ?? 'Please correct the marked fields.');
        }
    }

    protected function runFormSave(?int $id, array $values, ?string &$formError): ?int
    {
        $text = $this->getText();

        try {
            if (null === $id) {
                return $this->service->create($values);
            }

            $this->service->update($id, $values);

            return $id;
        } catch (AccessDeniedException $e) {
            $this->logDenied($e);
            $formError = $text['saveDenied'] ?? 'The record could not be saved with these values.';
        } catch (\InvalidArgumentException $e) {
            $formError = $this->sanitizeMessage($e->getMessage());
        } catch (\Throwable $e) {
            // Mit JavaScript: zentrale Fehlerbehandlung (500), das Formular im Browser bleibt erhalten
            if ($this->ajax) {
                throw $e;
            }

            $this->logError($e);
            $formError = AjaxResponse::CODE_DATABASE === AjaxResponse::classify($e)
                ? ($text['databaseError'] ?? 'The data could not be saved.')
                : ($text['error'] ?? 'An unexpected error occurred.');
        }

        return null;
    }

    protected function renderDeleteConfirmation(int $id): void
    {
        $this->guard->assertOperation(ManagerConfig::OPERATION_DELETE);
        $record = $this->service->requirePermitted($id);
        $titleField = $this->guard->getListFields()[0] ?? null;
        $title = null !== $titleField ? $this->toDisplayString($record[$titleField] ?? '') : '';

        $this->Template->view = 'delete';
        $this->Template->record = [
            'id' => $id,
            'title' => '' !== $title ? $title : '#' . $id,
            'cancelUrl' => $this->buildUrl([]),
            'actionUrl' => $this->buildUrl([], true),
        ];
        $this->viewUrl = $this->buildUrl(['cmfm_act' => 'delete', 'cmfm_id' => $id]);
    }

    // ---------------------------------------------------------------
    // Hilfsmethoden
    // ---------------------------------------------------------------

    protected function getText(): array
    {
        return (array)($GLOBALS['TL_LANG']['MSC']['cmFrontendManager'] ?? []);
    }

    protected function buildFilters(array $active): array
    {
        $filters = [];
        $dc = new HeadlessDataContainer($this->config->table, null, new RecordStub([]));

        foreach ($this->guard->getFilterFields() as $field) {
            $cfg = $this->guard->getPolicy()->getFieldConfig($field);
            $type = $cfg['inputType'] ?? 'text';
            $options = [];

            if (\in_array($type, ['select', 'radio'], true) || ('checkbox' === $type && !empty($cfg['eval']['multiple']))) {
                try {
                    $attributes = Widget::getAttributesFromDca($cfg, $field, null, $field, $this->config->table, $dc);
                    foreach ($attributes['options'] ?? [] as $option) {
                        if (isset($option['value']) && '' !== (string)$option['value']) {
                            $options[] = ['value' => (string)$option['value'], 'label' => strip_tags((string)($option['label'] ?? $option['value']))];
                        }
                    }
                } catch (\Throwable $e) {
                    $this->logError($e);
                }
                $type = 'select';
            } elseif ('checkbox' === $type) {
                $type = 'boolean';
            } else {
                $type = 'text';
            }

            $filters[] = [
                'name' => $field,
                'inputName' => 'cmfm_f[' . $field . ']',
                'id' => 'cmfm_' . $this->id . '_f_' . $field,
                'label' => $this->guard->getPolicy()->getLabel($field),
                'type' => $type,
                'options' => $options,
                'value' => $active[$field] ?? '',
            ];
        }

        return $filters;
    }

    protected function getDefaultValues(): array
    {
        $values = [];

        foreach ($this->dca['fields'] ?? [] as $field => $cfg) {
            if (\array_key_exists('default', $cfg)) {
                $values[$field] = \is_callable($cfg['default']) && !\is_string($cfg['default']) ? ($cfg['default'])() : $cfg['default'];
            }
        }

        return $values;
    }

    protected function getEditHeadline(int $id, array $record): string
    {
        $text = $this->getText();
        $title = $this->getRecordTitle($record);

        return null !== $title
            ? sprintf($text['editHeadlineTitle'] ?? 'Edit "%s"', $title)
            : sprintf($text['editHeadline'] ?? 'Edit entry %s', $id);
    }

    protected function getRecordTitle(array $record): ?string
    {
        $title = RoleResolver::getInstance($this->config->table, $record)->getValueByRole('title');
        $title = $this->toDisplayString(\is_string($title) || \is_array($title) ? StringUtil::deserialize($title) : $title);

        return '' !== $title ? mb_strimwidth($title, 0, 120, '…') : null;
    }

    protected function toDisplayString(mixed $value): string
    {
        if (\is_array($value)) {
            // Optionen liefern [{value, label}], Bilder/Dateien [{img, path, alt, title …}]
            $value = implode(', ', array_filter(array_map(static function ($item): string {
                if (!\is_array($item)) {
                    return \is_scalar($item) ? (string)$item : '';
                }

                foreach (['label', 'title', 'alt', 'name'] as $key) {
                    if (isset($item[$key]) && \is_scalar($item[$key]) && '' !== trim((string)$item[$key])) {
                        return (string)$item[$key];
                    }
                }

                return isset($item['path']) && \is_string($item['path']) ? basename($item['path']) : '';
            }, $value), static fn (string $v): bool => '' !== trim($v)));
        }

        return trim(StringUtil::decodeEntities(strip_tags(\is_scalar($value) ? (string)$value : '')));
    }

    protected function sanitizeMessage(string $message): string
    {
        // Fachliche Meldungen des DcaRecordManager (Pflichtfeld, eindeutiger Wert …) ohne Tabellen-/Tool-Hinweise
        $message = preg_replace('/\s*Mögliche Felder liefert config_describe\.?/u', '', $message) ?? $message;

        return mb_substr(strip_tags($message), 0, 300);
    }

    protected function isOwnSubmission(): bool
    {
        return Input::post('FORM_SUBMIT') === $this->getFormSubmitId() && $this->isTokenValid();
    }

    protected function isTokenValid(): bool
    {
        $container = System::getContainer();
        $token = Input::post('REQUEST_TOKEN');

        return \is_string($token) && '' !== $token && $container->get('contao.csrf.token_manager')->isTokenValid(
            new CsrfToken((string)$container->getParameter('contao.csrf_token_name'), $token)
        );
    }

    protected function getFormSubmitId(): string
    {
        return 'cm_fm_' . (int)$this->id;
    }

    protected function getRequestToken(): string
    {
        return (string)System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
    }

    protected function getRequest(): Request
    {
        return System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create('/');
    }

    protected function buildUrl(array $params, bool $keepListState = false): string
    {
        $request = $this->getRequest();
        $query = $request->query->all();
        unset($query[self::AJAX_PARAM]);

        foreach (array_keys($query) as $key) {
            if (str_starts_with((string)$key, 'cmfm_') && (!$keepListState || \in_array($key, ['cmfm_act', 'cmfm_id'], true))) {
                unset($query[$key]);
            }
        }

        if (!$keepListState || isset($params['cmfm_sort'])) {
            unset($query['page_e' . $this->id]);
        }

        $query = array_merge($query, array_filter($params, static fn ($v) => null !== $v && '' !== $v));
        $url = $request->getBaseUrl() . $request->getPathInfo();

        return $url . ([] !== $query ? '?' . http_build_query($query) : '');
    }

    protected function redirectTo(array $params): never
    {
        throw new RedirectResponseException($this->buildUrl($params, [] === $params), 303);
    }

    protected function addFlash(string $type, string $text): void
    {
        $request = $this->getRequest();

        if ($request->hasSession()) {
            $request->getSession()->getFlashBag()->add($this->getFormSubmitId(), ['type' => $type, 'text' => $text]);
        }
    }

    protected function pullFlashMessages(): array
    {
        $request = $this->getRequest();

        if (!$request->hasPreviousSession()) {
            return [];
        }

        return array_values(array_filter(
            $request->getSession()->getFlashBag()->get($this->getFormSubmitId()),
            static fn ($m) => \is_array($m) && isset($m['type'], $m['text'])
        ));
    }

    protected function logDenied(AccessDeniedException $e): void
    {
        $container = System::getContainer();

        if ($container->has('monolog.logger.contao.access')) {
            $container->get('monolog.logger.contao.access')->info(sprintf('Catalog frontend manager (module %d): %s', $this->id, $e->getMessage()));
        }
    }

    protected function logError(\Throwable $e): void
    {
        $container = System::getContainer();

        if ($container->has('monolog.logger.contao.error')) {
            $container->get('monolog.logger.contao.error')->error(sprintf('Catalog frontend manager (module %d): %s', $this->id, $e->getMessage()), ['exception' => $e]);
        }
    }
}
