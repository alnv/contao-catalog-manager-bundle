<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

class Reactions {

    protected string $strTable;
    protected string $strCatalogReactionId;

    public function __construct($strTable, $strCatalogReactionsId) {

        $objCombiner = new \Combiner();
        $objCombiner->add('bundles/alnvcontaocatalogmanager/css/reactions.scss');

        $GLOBALS['TL_CSS']['reactions'] = $objCombiner->getCombinedFile();

        $this->strTable = $strTable;
        $this->strCatalogReactionId = $strCatalogReactionsId;

        if ($strRequest = \Input::get('_req')) {

            $arrRequest = \StringUtil::deserialize(base64_decode($strRequest));
            if (!is_array($arrRequest) || empty($arrRequest)) {
                $this->reload();
            }

            if (!$arrRequest['key']) {
                $this->reload();
            }

            $objCurrentReaction = \Alnv\ContaoCatalogManagerBundle\Models\CatalogReactionsDataModel::getReaction($this->strTable, $arrRequest['id']);

            if (!$objCurrentReaction) {
                $objCurrentReaction = new \Alnv\ContaoCatalogManagerBundle\Models\CatalogReactionsDataModel();
                $objCurrentReaction->created_at = time();
                $objCurrentReaction->session = \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getSessionId();
                $objCurrentReaction->member = \FrontendUser::getInstance()->id ?: 0;
                $objCurrentReaction->table = $this->strTable;
                $objCurrentReaction->reaction = $this->strCatalogReactionId;
                $objCurrentReaction->identifier = $arrRequest['id'];
            } else {
                if ($objCurrentReaction->reaction_key == $arrRequest['key']) {
                    $objCurrentReaction->delete();
                    $this->reload();
                }
            }

            $objCurrentReaction->tstamp = time();
            $objCurrentReaction->reaction_key = $arrRequest['key'] ?? '';
            $objCurrentReaction->save();

            $this->reload();
        }
    }

    public function count($strIdentifier, $strKey) {

        $objCount = \Database::getInstance()->prepare('SELECT * FROM tl_catalog_reactions_data WHERE `table`=? AND identifier=? AND reaction_key=?')->execute($this->strTable, $strIdentifier, $strKey);

        return $objCount->numRows;
    }

    public function getReactions($strIdentifier) {

        $objReaction = \Alnv\ContaoCatalogManagerBundle\Models\CatalogReactionsModel::findByPk($this->strCatalogReactionId);

        if (!$objReaction) {
            return '';
        }

        $strId = uniqid();
        $arrReactions = [];
        $objTemplate = new \FrontendTemplate($objReaction->template);
        $arrActiveReaction = $this->getActiveReaction($strIdentifier);

        foreach (\StringUtil::deserialize($objReaction->reactions, true) as $arrReaction) {

            if (!$arrReaction['key']) {
                continue;
            }

            $arrReactions[] = [
                'key' => $arrReaction['key'],
                'name' => $arrReaction['name'] ?? '',
                'data' => $arrActiveReaction,
                'count' => $this->count($strIdentifier, $arrReaction['key']),
                'id' => 'reid_' . $arrReaction['key'] . '_' . $strId,
                'active' => !empty($arrActiveReaction) && ($arrActiveReaction['reaction_key'] == $arrReaction['key']),
                'href' => $this->getHrefByIdentifier($strIdentifier, $arrReaction),
                'icon' => $this->getIcon($arrReaction['icon']),
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

        $objTemplate->setData([
            'id' => 'reaction-' . $this->strCatalogReactionId,
            'reaction' => $arrFirstReaction,
            'reactions' => $arrReactions
        ]);

        return $objTemplate->parse();
    }

    protected function getAlias () : string {

        return $_GET['auto_item'] ? '/' . \Input::get('auto_item') : '';
    }

    protected function reload() {

        global $objPage;

        if (!$objPage) {
            return null;
        }

        \Controller::redirect($objPage->getFrontendUrl($this->getAlias()));
    }

    protected function getHrefByIdentifier($strIdentifier, $arrReaction) : string {

        global $objPage;

        if (!$objPage) {
            return '';
        }

        $arrOptions = [
            'id' => $strIdentifier,
            'key' => $arrReaction['key']
        ];

        return $objPage->getFrontendUrl($this->getAlias()) . '?_req=' . base64_encode(serialize($arrOptions));
    }

    protected function getActiveReaction($strIdentifier) : array {

        $objReactionData = \Alnv\ContaoCatalogManagerBundle\Models\CatalogReactionsDataModel::getReaction($this->strTable, $strIdentifier);

        if (!$objReactionData) {
            return [];
        }

        return $objReactionData->row();
    }

    protected function getIcon($strUuid) : string {

        $objFile = \FilesModel::findByUuid($strUuid);

        if (!$objFile) {
            return '';
        }

        return $objFile->path;
    }
}