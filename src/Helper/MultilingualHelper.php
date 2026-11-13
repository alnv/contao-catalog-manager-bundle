<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Contao\Database;

class MultilingualHelper
{
    public const CACHE_KEY = 'cm_multilingual_root_languages';

    public static function getLanguages(): array
    {
        if (Cache::has(self::CACHE_KEY)) {
            return Cache::get(self::CACHE_KEY);
        }

        $arrLanguages = ['languages' => [], 'fallback' => '', 'fallbacks' => []];
        $objPages = Database::getInstance()
            ->prepare('SELECT fallback,`language` FROM tl_page WHERE `type`=?')
            ->execute('root');

        while ($objPages->next()) {
            if ($objPages->fallback) {
                $arrLanguages['fallbacks'][] = $objPages->language;
            }

            if ($objPages->language && !\in_array($objPages->language, $arrLanguages['languages'])) {
                $arrLanguages['languages'][] = $objPages->language;
            }
        }

        if (!empty($arrLanguages['fallbacks']) && \count($arrLanguages['fallbacks']) < 2) {
            $arrLanguages['fallback'] = $arrLanguages['fallbacks'][0] ?? '';
        }

        Cache::set(self::CACHE_KEY, $arrLanguages);

        return $arrLanguages;
    }
}
