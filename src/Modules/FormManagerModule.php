<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

class FormManagerModule extends \Module {

    protected $arrActiveRecord = [];
    protected $strMemberField = null;
    protected $strTemplate = 'mod_form_manager';

    public function generate() {

        if ( \System::getContainer()->get( 'request_stack' )->getCurrentRequest()->get('_scope') == 'backend' ) {

            $objTemplate = new \BackendTemplate('be_wildcard');
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->title = $this->headline;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
            $objTemplate->wildcard = '### ' . strtoupper( $GLOBALS['TL_LANG']['FMD']['form-manager'][0] ) . ' ###';

            return $objTemplate->parse();
        }

        $objMember = \FrontendUser::getInstance();
        $this->strMemberField = \Alnv\ContaoCatalogManagerBundle\Library\RoleResolver::getInstance($this->cmIdentifier, [])->getFieldByRole('member');
        $this->arrActiveRecord = $this->getActiveRecord();

        if ( $this->strMemberField && !empty($this->arrActiveRecord) ) {
            if ( !$objMember->id || ( $this->arrActiveRecord[$this->strMemberField] != $objMember->id ) ) {
                throw new \CoreBundle\Exception\InsufficientAuthenticationException('Page access denied:  ' . \Environment::get('uri'));
            }
        }

        return parent::generate();
    }

    protected function compile() {

        $this->Template->source = $this->cmSource;
        $this->Template->formHint = $this->cmFormHint;
        $this->Template->identifier = $this->cmIdentifier;
        $this->Template->successRedirect = $this->getFrontendUrl($this->cmSuccessRedirect);
        $this->Template->model = htmlspecialchars(json_encode($this->arrActiveRecord),ENT_QUOTES,'UTF-8');
    }

    protected function getFrontendUrl($strPageId) {
        if (!$strPageId) {
            return '';
        }
        $objPage = \PageModel::findByPk($strPageId);
        if ( $objPage === null ) {
            return '';
        }
        return $objPage->getFrontendUrl();
    }

    protected function getActiveRecord() {
        $arrActiveRecord = [];
        if ($this->cmSource != 'dc') {
            return $arrActiveRecord;
        }
        /*
        $objMember = \FrontendUser::getInstance();
        if ($objMember->id) {
            $objRoleResolver = \Alnv\ContaoCatalogManagerBundle\Library\RoleResolver::getInstance($this->cmIdentifier,$arrActiveRecord);
            $strMember = $objRoleResolver->getFieldByRole('member');
            if ($this->strMember) {
                $arrActiveRecord[$strMember] = $objMember->id;
            }
        }
        */
        if (!isset($_GET['auto_item'])) {
            return $arrActiveRecord;
        }
        $objMaster = new \Alnv\ContaoCatalogManagerBundle\Views\Master( $this->cmIdentifier, [
            'alias' => \Input::get('auto_item'),
            'id' => $this->id
        ]);
        $arrMaster = $objMaster->parse();
        if (empty($arrMaster)) {
            return $arrActiveRecord;
        }
        $arrFields = array_keys($GLOBALS['TL_DCA'][$this->cmIdentifier]['fields']);
        if (!is_array($arrFields) || empty($arrFields)) {
            return $arrActiveRecord;
        }
        foreach ($arrFields as $strField) {
            $arrActiveRecord[$strField] = $arrMaster[0][$strField];
        }
        return $arrActiveRecord;
    }
}