<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\CatalogTableGuard;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogReactionsDataModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogReactionsModel;
use Contao\Combiner;
use Contao\Controller;
use Contao\Database;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\StringUtil;
use Contao\System;

class Reactions
{
    public const FORM_PREFIX = 'cm_reaction_';

    protected string $strTable;

    protected string $strCatalogReactionId;

    public function __construct($strTable, $strCatalogReactionsId)
    {
        $objCombiner = new Combiner();
        $objCombiner->add('bundles/alnvcontaocatalogmanager/css/reactions.scss');

        $GLOBALS['TL_CSS']['reactions'] = $objCombiner->getCombinedFile();

        $this->strTable = (string)$strTable;
        $this->strCatalogReactionId = (string)$strCatalogReactionsId;

        $this->handleSubmit();
    }

    protected function handleSubmit(): void
    {
        $objRequest = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($objRequest === null || !$objRequest->isMethod('POST') || $objRequest->request->get('FORM_SUBMIT') !== $this->getFormId()) {
            return;
        }

        $strIdentifier = (string)$objRequest->request->get('identifier', '');
        $strKey = (string)$objRequest->request->get('reaction', '');

        if (!$this->isValidSubmission($strIdentifier, $strKey)) {
            Controller::reload();
        }

        // Rate-Limit pro IP: zu viele Reaktionen → ignorieren
        $objContainer = System::getContainer();
        if ($objContainer->has('catalogmanager.rate_limiter.reactions')) {
            $objLimiter = $objContainer->get('catalogmanager.rate_limiter.reactions')->create('reaction-' . ($objRequest->getClientIp() ?? 'unknown'));
            if (!$objLimiter->consume()->isAccepted()) {
                Controller::reload();
            }
        }

        $objCurrentReaction = CatalogReactionsDataModel::getReaction($this->strTable, $strIdentifier);

        if ($objCurrentReaction !== null && $objCurrentReaction->reaction_key === $strKey) {
            // Gleiche Reaktion erneut → zurücknehmen
            $objCurrentReaction->delete();
            Controller::reload();
        }

        if ($objCurrentReaction === null) {
            $objCurrentReaction = new CatalogReactionsDataModel();
            $objCurrentReaction->created_at = time();
            $objCurrentReaction->session = Toolkit::getSessionId();
            $objCurrentReaction->member = FrontendUser::getInstance()->id ?: 0;
            $objCurrentReaction->table = $this->strTable;
            $objCurrentReaction->reaction = $this->strCatalogReactionId;
            $objCurrentReaction->identifier = $strIdentifier;
        }

        $objCurrentReaction->tstamp = time();
        $objCurrentReaction->reaction_key = $strKey;
        $objCurrentReaction->save();

        Controller::reload();
    }

    protected function isValidSubmission(string $strIdentifier, string $strKey): bool
    {
        if (!ctype_digit($strIdentifier) || (int)$strIdentifier < 1 || $strKey === '') {
            return false;
        }

        if (!\in_array($strKey, $this->getReactionKeys(), true)) {
            return false;
        }

        if (!CatalogTableGuard::isCatalogTable($this->strTable)) {
            return false;
        }

        return Database::getInstance()
                ->prepare('SELECT id FROM ' . Database::quoteIdentifier($this->strTable) . ' WHERE id=?')
                ->limit(1)
                ->execute((int)$strIdentifier)
                ->numRows > 0;
    }

    protected function getReactionKeys(): array
    {
        $objReaction = CatalogReactionsModel::findByPk($this->strCatalogReactionId);

        if ($objReaction === null) {
            return [];
        }

        $arrKeys = [];
        foreach (StringUtil::deserialize($objReaction->reactions, true) as $arrReaction) {
            if (!empty($arrReaction['key'])) {
                $arrKeys[] = (string)$arrReaction['key'];
            }
        }

        return $arrKeys;
    }

    protected function getFormId(): string
    {
        return self::FORM_PREFIX . $this->strCatalogReactionId;
    }

    public function count($strIdentifier, $strKey)
    {
        return (int)Database::getInstance()
            ->prepare('SELECT COUNT(*) AS cnt FROM tl_catalog_reactions_data WHERE `table`=? AND identifier=? AND reaction_key=?')
            ->execute($this->strTable, $strIdentifier, $strKey)
            ->cnt;
    }

    public function getReactions($strIdentifier)
    {
        $objReaction = CatalogReactionsModel::findByPk($this->strCatalogReactionId);

        if (!$objReaction) {
            return '';
        }

        $strId = uniqid();
        $arrReactions = [];
        $objTemplate = new FrontendTemplate($objReaction->template);
        $arrActiveReaction = $this->getActiveReaction($strIdentifier);

        foreach (StringUtil::deserialize($objReaction->reactions, true) as $arrReaction) {

            if (empty($arrReaction['key'])) {
                continue;
            }

            $arrReactions[] = [
                'key' => $arrReaction['key'],
                'name' => $arrReaction['name'] ?? '',
                'data' => $arrActiveReaction,
                'count' => $this->count($strIdentifier, $arrReaction['key']),
                'id' => 'reid_' . $arrReaction['key'] . '_' . $strId,
                'active' => !empty($arrActiveReaction) && ($arrActiveReaction['reaction_key'] == $arrReaction['key']),
                'href' => '', // veraltet: Reaktionen werden per POST-Formular abgegeben
                'icon' => $this->getIcon($arrReaction['icon'] ?? ''),
            ];
        }

        $arrFirstReaction = [];
        if (empty($arrActiveReaction)) {
            $arrFirstReaction = $arrReactions[0] ?? [];
            unset($arrReactions[0]);
        } else {
            foreach ($arrReactions as $strIndex => $arrReaction) {
                if ($arrReaction['key'] == $arrActiveReaction['reaction_key']) {
                    $arrFirstReaction = $arrReaction;
                    unset($arrReactions[$strIndex]);
                }
            }
        }

        $objRequest = System::getContainer()->get('request_stack')->getCurrentRequest();

        $objTemplate->setData([
            'id' => 'reaction-' . $this->strCatalogReactionId . '-' . $strIdentifier,
            'reaction' => $arrFirstReaction,
            'reactions' => $arrReactions,
            'action' => $objRequest ? $objRequest->getRequestUri() : '',
            'formId' => $this->getFormId(),
            'identifier' => $strIdentifier,
            'requestToken' => System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue(),
        ]);

        return $objTemplate->parse();
    }

    protected function getActiveReaction($strIdentifier): array
    {
        $objReactionData = CatalogReactionsDataModel::getReaction($this->strTable, $strIdentifier);

        if (!$objReactionData) {
            return [];
        }

        return $objReactionData->row();
    }

    protected function getIcon($strUuid): string
    {
        if (!$strUuid) {
            return '';
        }

        $objFile = FilesModel::findByUuid($strUuid);
        if (!$objFile) {
            return '';
        }

        return $objFile->path;
    }
}
