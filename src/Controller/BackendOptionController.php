<?php

declare(strict_types=1);

namespace Alnv\ContaoCatalogManagerBundle\Controller;

use Alnv\ContaoCatalogManagerBundle\Helper\CatalogTableGuard;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogOptionModel;
use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\Database;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;

#[Route(path: '%contao.backend.route_prefix%/catalog-manager/option', defaults: ['_scope' => 'backend'])]
final class BackendOptionController extends AbstractController
{
    #[Route(path: '/add', name: 'catalog_manager_backend_option_add', methods: ['POST'])]
    public function addOption(Request $request): JsonResponse
    {
        $this->initializeContaoFramework();
        $this->assertBackendRequest($request);

        $field = $this->resolveField($request);

        $label = $this->sanitizeLabel((string) $request->request->get('option', ''));
        if ('' === $label) {
            return new JsonResponse(['error' => 'Invalid option label.'], 400);
        }

        switch ($field->optionsSource) {
            case 'options':
                $option = new CatalogOptionModel();
                $option->pid = $field->id;
                $option->tstamp = time();
                $option->label = StringUtil::specialchars($label);
                $option->value = Toolkit::generateAlias($label, 'value', 'tl_catalog_option');
                $option->save();

                return new JsonResponse(['value' => $option->value, 'label' => $option->label]);

            case 'dbOptions':
                $dbTable = $this->assertDbOptionSource($field);

                $set = [
                    'tstamp' => time(),
                    $field->dbLabel => StringUtil::specialchars($label),
                ];

                if (Database::getInstance()->fieldExists('alias', $dbTable)) {
                    $set['alias'] = Toolkit::generateAlias($label, 'alias', $dbTable);
                }

                $this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $dbTable, new CreateAction($dbTable, $set));

                $insertId = Database::getInstance()
                    ->prepare('INSERT INTO ' . Database::quoteIdentifier($dbTable) . ' %s')
                    ->set($set)
                    ->execute()
                    ->insertId;

                if ('id' === $field->dbKey) {
                    $value = (string) $insertId;
                } else {
                    // Schlüssel = Alias (oder anderer Wert): nach dem Insert nachlesen
                    $row = Database::getInstance()
                        ->prepare('SELECT * FROM ' . Database::quoteIdentifier($dbTable) . ' WHERE id=?')
                        ->limit(1)
                        ->execute($insertId)
                        ->row();
                    $value = (string) ($row[$field->dbKey] ?? '');
                }

                return new JsonResponse(['value' => $value, 'label' => $set[$field->dbLabel]]);
        }

        return new JsonResponse(['error' => 'Unsupported options source.'], 400);
    }

    #[Route(path: '/delete', name: 'catalog_manager_backend_option_delete', methods: ['POST'])]
    public function deleteOption(Request $request): JsonResponse
    {
        $this->initializeContaoFramework();
        $this->assertBackendRequest($request);

        $field = $this->resolveField($request);

        $value = (string) $request->request->get('option', '');
        $index = (int) $request->request->get('index', 0);

        if ('' === $value) {
            return new JsonResponse(['error' => 'Missing option.'], 400);
        }

        switch ($field->optionsSource) {
            case 'options':
                $option = CatalogOptionModel::findByValueAndPid($value, $field->id);
                if (null !== $option) {
                    $option->delete();
                }
                break;

            case 'dbOptions':
                $dbTable = $this->assertDbOptionSource($field);

                $row = Database::getInstance()
                    ->prepare('SELECT * FROM ' . Database::quoteIdentifier($dbTable) . ' WHERE ' . Database::quoteIdentifier($field->dbKey) . '=?')
                    ->limit(1)
                    ->execute($value)
                    ->row();

                if (empty($row)) {
                    break;
                }

                $this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $dbTable, new DeleteAction($dbTable, $row));

                Database::getInstance()
                    ->prepare('DELETE FROM ' . Database::quoteIdentifier($dbTable) . ' WHERE id=?')
                    ->limit(1)
                    ->execute($row['id']);
                break;

            default:
                return new JsonResponse(['error' => 'Unsupported options source.'], 400);
        }

        return new JsonResponse(['index' => $index, 'value' => $value]);
    }

    private function assertBackendRequest(Request $request): void
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->getUser() instanceof BackendUser) {
            throw $this->createAccessDeniedException('Backend user required.');
        }

        $tokenValue = (string) ($request->request->get('REQUEST_TOKEN') ?? $request->headers->get('X-CSRF-Token', ''));
        $tokenName = (string) $this->getParameter('contao.csrf_token_name');

        if ('' === $tokenValue || !$this->container->get('contao.csrf.token_manager')->isTokenValid(new CsrfToken($tokenName, $tokenValue))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }

    private function resolveField(Request $request): CatalogFieldModel
    {
        $table = (string) $request->request->get('table', '');
        $fieldName = (string) $request->request->get('name', '');

        if (!CatalogTableGuard::isValidIdentifier($table) || !CatalogTableGuard::isValidIdentifier($fieldName)) {
            throw $this->createNotFoundException();
        }

        $catalog = CatalogModel::findOneBy(['tl_catalog.`table`=?'], [$table]);
        if (null === $catalog) {
            throw $this->createNotFoundException();
        }

        $field = CatalogFieldModel::findByFieldnameAndPid($fieldName, $catalog->id);
        if (null === $field) {
            throw $this->createNotFoundException();
        }

        $this->assertModuleAccess($table);

        Controller::loadDataContainer($table);
        $fieldDca = $GLOBALS['TL_DCA'][$table]['fields'][$fieldName] ?? null;

        if (null === $fieldDca) {
            throw $this->createNotFoundException();
        }

        // Contao 5: Felder sind standardmäßig "exclude" → Feldrecht nötig (Admins haben es immer)
        if (false !== ($fieldDca['exclude'] ?? true)) {
            $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $table . '::' . $fieldName);
        }

        return $field;
    }

    private function assertModuleAccess(string $table): void
    {
        foreach ($GLOBALS['BE_MOD'] ?? [] as $modules) {
            foreach ($modules as $name => $config) {
                if (\in_array($table, (array) ($config['tables'] ?? []), true) && $this->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, $name)) {
                    return;
                }
            }
        }

        throw $this->createAccessDeniedException(sprintf('No access to a backend module containing "%s".', $table));
    }

    private function assertDbOptionSource(CatalogFieldModel $field): string
    {
        $dbTable = (string) $field->dbTable;
        $db = Database::getInstance();

        if (
            !CatalogTableGuard::isValidIdentifier($dbTable)
            || !CatalogTableGuard::isValidIdentifier((string) $field->dbKey)
            || !CatalogTableGuard::isValidIdentifier((string) $field->dbLabel)
            || !$db->tableExists($dbTable)
            || !$db->fieldExists($field->dbKey, $dbTable)
            || !$db->fieldExists($field->dbLabel, $dbTable)
        ) {
            throw $this->createNotFoundException('Invalid option source.');
        }

        Controller::loadDataContainer($dbTable);

        return $dbTable;
    }

    private function sanitizeLabel(string $label): string
    {
        $label = StringUtil::decodeEntities($label);
        $label = strip_tags($label);
        $label = str_replace(['{{', '}}'], '', $label);
        $label = trim(preg_replace('/\s+/u', ' ', $label) ?? '');

        return mb_substr($label, 0, 255);
    }
}
