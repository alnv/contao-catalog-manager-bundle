<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Contao\FrontendTemplate;

class ShareButtons
{

    protected array $arrShareButtons = [
        'twitter',
        'facebook',
        'linkedin',
        'xing',
        'email',
    ];

    protected array $arrEntity = [];

    public function __construct($arrEntity)
    {

        $this->arrEntity = $arrEntity;
    }

    public function getShareButtons($arrButtons = []): string
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