<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Contao\FrontendTemplate;

class ShareButtons
{

    protected $arrShareButtons = [

        'twitter',
        'facebook',
        'linkedin',
        'xing',
        'email',
    ];

    protected $arrEntity = [];

    public function __construct($arrEntity)
    {

        $this->arrEntity = $arrEntity;
    }

    public function getShareButtons($arrButtons = [])
    {

        $strTemplate = '';

        if (!empty($arrButtons) && \is_array($arrButtons)) {

            $this->arrShareButtons = $arrButtons;
        }

        foreach ($this->arrShareButtons as $strButton) {

            $objTemplate = new FrontendTemplate('cm_share_button_' . $strButton);
            $objTemplate->setData($this->arrEntity);
            $strTemplate .= $objTemplate->parse();
        }

        return $strTemplate;
    }
}