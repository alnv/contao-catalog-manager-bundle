<?php

use Alnv\ContaoCatalogManagerBundle\Elements\ContentListView;
use Alnv\ContaoCatalogManagerBundle\Helper\Mode;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogDataModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogOptionModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogPaletteModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogReactionsDataModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogReactionsModel;
use Alnv\ContaoCatalogManagerBundle\Models\WatchlistModel;
use Alnv\ContaoCatalogManagerBundle\Modules\ListingModule;
use Alnv\ContaoCatalogManagerBundle\Modules\MapModule;
use Alnv\ContaoCatalogManagerBundle\Modules\MasterModule;
use Alnv\ContaoCatalogManagerBundle\AI\AiChatComponentParser;
use Alnv\ContaoCatalogManagerBundle\Widgets\CustomOptionWizard;
use Contao\ArrayUtil;
use Contao\Combiner;
use Contao\DC_Table;

const CATALOG_MANAGER_BUNDLE_VERSION = "3.4.16";

ArrayUtil::arrayInsert($GLOBALS['BE_MOD'], 2, [
    'catalog-manager-bundle' => [
        'catalog-manager' => [
            'name' => 'catalog-manager-bundle',
            'tables' => [
                'tl_catalog',
                'tl_catalog_field',
                'tl_catalog_option',
                'tl_catalog_palette',
                'tl_catalog_license',
                'tl_catalog_vector_files'
            ]
        ],
        'roles' => [
            'name' => 'roles',
            'tables' => [
                'tl_catalog_roles'
            ]
        ],
        'reactions' => [
            'name' => 'reactions',
            'tables' => [
                'tl_catalog_reactions'
            ]
        ],
        'watchlist' => [
            'name' => 'watchlist',
            'tables' => [
                'tl_watchlist'
            ]
        ]
    ]
]);

foreach ($GLOBALS['BE_MOD'] as $strType => $arrModules) {
    foreach ($arrModules as $strModule => $arrModule) {
        if (\in_array('tl_page', ($arrModule['tables'] ?? []))) {
            $GLOBALS['BE_MOD'][$strType][$strModule]['tables'][] = 'tl_page_filter';
        }
    }
}

ArrayUtil::arrayInsert($GLOBALS['FE_MOD'], 2, [
    'catalog-manager-bundle' => [
        'listing-map' => MapModule::class,
        'listing-table' => ListingModule::class,
        'master' => MasterModule::class
    ]
]);

$GLOBALS['OPEN_AI_MESSAGE_PARSER']['catalog-manager'] = [
    'label' => 'Catalog-Manager',
    'class' => AiChatComponentParser::class
];

$GLOBALS['TL_CTE']['catalog-manager-bundle'] = [];
$GLOBALS['TL_CTE']['catalog-manager-bundle']['listview'] = ContentListView::class;

$GLOBALS['CM_MASTER'] = [];
$GLOBALS['CM_MODELS'] = [];
$GLOBALS['CM_CUSTOM_FIELDS'] = [];
$GLOBALS['CM_DATA_CONTAINERS'] = ['Table'];
$GLOBALS['CM_DATA_CONTAINERS_NAMESPACE'] = ['Table' => DC_Table::class];
$GLOBALS['CM_FIELDS'] = ['text', 'color', 'date', 'textarea', 'select', 'radio', 'checkbox', 'checkboxWizard', 'customOptionWizard', 'pagepicker', 'upload', 'explanation', 'empty', 'listWizard'];

$GLOBALS['BE_FFL']['customOptionWizard'] = CustomOptionWizard::class;

$GLOBALS['CM_OPERATORS'] = [
    'equal' => [
        'token' => '##field##=##value##'
    ],
    'notEqual' => [
        'token' => '##field##!=##value##'
    ],
    'findInSet' => [
        'token' => 'FIND_IN_SET(##field##,##value##)'
    ],
    'notFindInSet' => [
        'token' => 'NOT FIND_IN_SET(##field##,##value##)'
    ],
    'reversedFindInSet' => [
        'token' => 'FIND_IN_SET(##value##,##field##)'
    ],
    'reversedNotFindInSet' => [
        'token' => 'NOT FIND_IN_SET(##value##,##field##)'
    ],
    'regexp' => [
        'token' => 'LOWER(CAST(##field## AS CHAR)) REGEXP LOWER(CAST(##value## AS CHAR))'
    ],
    'notregexp' => [
        'token' => 'LOWER(CAST(##field## AS CHAR)) NOT REGEXP LOWER(CAST(##value## AS CHAR))'
    ],
    'between' => [
        'token' => '##field## BETWEEN CAST(##value##  AS DECIMAL(5,2)) AND CAST(##value##  AS DECIMAL(5,2))',
        'valueNumber' => 2
    ],
    'greater' => [
        'token' => '##field##>##value##'
    ],
    'greaterEqual' => [
        'token' => '##field##>=##value##'
    ],
    'lower' => [
        'token' => '##field##<##value##'
    ],
    'lowerEqual' => [
        'token' => '##field##<=##value##'
    ],
    'isNotEmpty' => [
        'token' => '(##field##!="" OR ##field## IS NOT NULL)',
        'empty' => true
    ],
    'isEmpty' => [
        'token' => '(##field##="" OR ##field## IS NULL)',
        'empty' => true
    ]
];

$GLOBALS['CM_ROLES'] = [
    'miscellaneous' => [
        'group' => 'miscellaneous',
        'sql' => "blob NULL"
    ],
    'serializejson' => [
        'group' => 'miscellaneous',
        'sql' => "mediumblob NULL"
    ],
    'title' => [
        'group' => 'article',
        'eval' => [
            'maxlength' => 255
        ],
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'metaTitle' => [
        'group' => 'meta',
        'eval' => [
            'maxlength' => 255
        ],
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'metaDescription' => [
        'group' => 'meta',
        'eval' => [],
        'sql' => "text NULL"
    ],
    'headline' => [
        'group' => 'article',
        'eval' => [
            'maxlength' => 255
        ],
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'textfield' => [
        'group' => 'article',
        'eval' => [
            'maxlength' => 255
        ],
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'page' => [
        'group' => 'article',
        'eval' => [
            'rgxp' => 'natural'
        ],
        'sql' => "int(10) unsigned NOT NULL default '0'"
    ],
    'pages' => [
        'group' => 'article',
        'sql' => "blob NULL"
    ],
    'redirects' => [
        'group' => 'article',
        'sql' => "blob NULL"
    ],
    'member' => [
        'group' => 'member',
        'eval' => [
            'rgxp' => 'natural'
        ],
        'sql' => "int(10) unsigned NULL"
    ],
    'members' => [
        'group' => 'member',
        'eval' => [
            'csv' => ','
        ],
        'sql' => "TINYTEXT NULL"
    ],
    'group' => [
        'group' => 'member',
        'eval' => [
            'csv' => 'natural'
        ],
        'sql' => "int(10) unsigned NULL"
    ],
    'groups' => [
        'group' => 'member',
        'sql' => "blob NULL"
    ],
    'subtitle' => [
        'group' => 'article',
        'eval' => [
            'maxlength' => 255
        ],
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'tags' => [
        'group' => 'article',
        'sql' => "blob NULL"
    ],
    'comma-separated-list' => [
        'group' => 'article',
        'eval' => [
            'csv' => ','
        ],
        'sql' => "text NULL"
    ],
    'alias' => [
        'group' => 'article',
        'eval' => [
            'rgxp' => 'alias',
            'doNotCopy' => true
        ],
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'type' => [
        'group' => 'article',
        'eval' => [
            'submitOnChange' => true
        ],
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'checkbox' => [
        'group' => 'article',
        'sql' => "char(1) NOT NULL default ''"
    ],
    'teaser' => [
        'group' => 'article',
        'sql' => "text NULL"
    ],
    'content' => [
        'group' => 'article',
        'sql' => "text NULL"
    ],
    'description' => [
        'group' => 'article',
        'sql' => "text NULL"
    ],
    'image' => [
        'group' => 'article',
        'type' => 'image',
        'sql' => "blob NULL"
    ],
    'files' => [
        'group' => 'article',
        'type' => 'files',
        'sql' => "blob NULL"
    ],
    'file' => [
        'group' => 'article',
        'type' => 'file',
        'sql' => "blob NULL"
    ],
    'gallery' => [
        'group' => 'article',
        'type' => 'gallery',
        'sql' => "blob NULL"
    ],
    'orderSRC' => [
        'group' => 'article',
        'sql' => "blob NULL"
    ],
    'hero' => [
        'group' => 'article',
        'type' => 'image',
        'sql' => "blob NULL"
    ],
    'duration' => [
        'group' => 'article',
        'eval' => [
            'rgxp' => 'alnum',
            'maxlength' => 64
        ],
        'sql' => "varchar(64) NOT NULL default '%s'"
    ],
    'price' => [
        'group' => 'shop',
        'eval' => [
            'rgxp' => 'alnum',
            'maxlength' => 64
        ],
        'sql' => "varchar(64) NOT NULL default '%s'"
    ],
    'vpe' => [
        'group' => 'shop',
        'eval' => [
            'rgxp' => 'alnum',
            'maxlength' => 64
        ],
        'sql' => "varchar(64) NOT NULL default '%s'"
    ],
    'ean' => [
        'group' => 'shop',
        'eval' => [
            'rgxp' => 'alnum',
            'maxlength' => 64
        ],
        'sql' => "varchar(64) NOT NULL default '%s'"
    ],
    'package' => [
        'group' => 'shop',
        'eval' => [
            'rgxp' => 'alnum',
            'maxlength' => 64
        ],
        'sql' => "varchar(64) NOT NULL default '%s'"
    ],
    'category' => [
        'group' => 'article',
        'eval' => [
            'rgxp' => 'alnum',
            'maxlength' => 128
        ],
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'author' => [
        'group' => 'article',
        'eval' => [
            'maxlength' => 128
        ],
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'location' => [
        'group' => 'address',
        'eval' => [
            'maxlength' => 128
        ],
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'street' => [
        'group' => 'address',
        'eval' => [
            'maxlength' => 128
        ],
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'streetNumber' => [
        'group' => 'address',
        'eval' => [
            'maxlength' => 16
        ],
        'sql' => "varchar(16) NOT NULL default '%s'"
    ],
    'city' => [
        'group' => 'address',
        'eval' => [
            'maxlength' => 128
        ],
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'zip' => [
        'group' => 'address',
        'eval' => [
            'rgxp' => 'alnum',
            'maxlength' => 12
        ],
        'sql' => "varchar(12) NOT NULL default '%s'"
    ],
    'postal' => [
        'group' => 'address',
        'eval' => [
            'rgxp' => 'alnum',
            'maxlength' => 12
        ],
        'sql' => "varchar(12) NOT NULL default '%s'"
    ],
    'state' => [
        'group' => 'address',
        'eval' => [
            'maxlength' => 128
        ],
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'country' => [
        'group' => 'address',
        'eval' => [
            'maxlength' => 128
        ],
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'address' => [
        'group' => 'address',
        'eval' => [
            'maxlength' => 255
        ],
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'fullname' => [
        'group' => 'person',
        'eval' => [
            'maxlength' => 255
        ],
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'firstname' => [
        'group' => 'person',
        'eval' => [
            'maxlength' => 128
        ],
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'lastname' => [
        'group' => 'person',
        'eval' => [
            'maxlength' => 128
        ],
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'email' => [
        'group' => 'contact',
        'eval' => [
            'rgxp' => 'email',
            'maxlength' => 128
        ],
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'emails' => [
        'group' => 'contact',
        'eval' => [
            'rgxp' => 'emails'
        ],
        'sql' => "text NULL"
    ],
    'url' => [
        'group' => 'contact',
        'eval' => [
            'rgxp' => 'url',
            'maxlength' => 255
        ],
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'website' => [
        'group' => 'contact',
        'eval' => [
            'maxlength' => 255,
            'rgxp' => 'url'
        ],
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'phone' => [
        'group' => 'contact',
        'eval' => [
            'maxlength' => 32,
            'rgxp' => 'phone'
        ],
        'sql' => "varchar(32) NOT NULL default '%s'"
    ],
    'fax' => [
        'group' => 'contact',
        'eval' => [
            'maxlength' => 32,
            'rgxp' => 'phone'
        ],
        'sql' => "varchar(32) NOT NULL default '%s'"
    ],
    'mobile' => [
        'group' => 'contact',
        'eval' => [
            'maxlength' => 32,
            'rgxp' => 'phone'
        ],
        'sql' => "varchar(32) NOT NULL default '%s'"
    ],
    'avatar' => [
        'group' => 'person',
        'type' => 'image',
        'sql' => "blob NULL"
    ],
    'company' => [
        'group' => 'contact',
        'eval' => [
            'maxlength' => 128
        ],
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'startDate' => [
        'group' => 'date',
        'eval' => [
            'rgxp' => 'date'
        ],
        'sql' => "int(11) signed NULL"
    ],
    'startTime' => [
        'group' => 'date',
        'eval' => [
            'rgxp' => 'time'
        ],
        'sql' => "int(11) signed NULL"
    ],
    'endDate' => [
        'group' => 'date',
        'eval' => [
            'rgxp' => 'date'
        ],
        'sql' => "int(11) signed NULL"
    ],
    'endTime' => [
        'group' => 'date',
        'eval' => [
            'rgxp' => 'time'
        ],
        'sql' => "int(11) signed NULL"
    ],
    'date' => [
        'group' => 'date',
        'eval' => [
            'rgxp' => 'date'
        ],
        'sql' => "int(11) signed NULL"
    ],
    'datim' => [
        'group' => 'date',
        'eval' => [
            'rgxp' => 'datim'
        ],
        'sql' => "int(11) signed NULL"
    ],
    'time' => [
        'group' => 'date',
        'eval' => [
            'rgxp' => 'time'
        ],
        'sql' => "int(11) signed NULL"
    ],
    'hasone' => [
        'group' => 'relation',
        'sql' => "int(10) unsigned NULL"
    ],
    'hasmany' => [
        'group' => 'relation',
        'sql' => "blob NULL"
    ],
    'latitude' => [
        'group' => 'geo',
        'eval' => [
            'maxlength' => 32
        ],
        'sql' => "varchar(32) NOT NULL default '0.000000'"
    ],
    'longitude' => [
        'group' => 'geo',
        'eval' => [
            'maxlength' => 32
        ],
        'sql' => "varchar(32) NOT NULL default '0.000000'"
    ],
    'gender' => [
        'group' => 'person',
        'eval' => [
            'maxlength' => 64
        ],
        'sql' => "varchar(64) NOT NULL default '%s'"
    ],
    'sku' => [
        'group' => 'product',
        'eval' => [
            'maxlength' => 32
        ],
        'sql' => "varchar(32) NOT NULL default '%s'"
    ],
    'decimal' => [
        'group' => 'number',
        'sql' => "decimal(10,8) NOT NULL default '0.000000'"
    ],
    'integer' => [
        'group' => 'number',
        'eval' => [
            'rgxp' => 'natural'
        ],
        'sql' => "int(10) unsigned NOT NULL default '0'"
    ],
    'signed' => [
        'group' => 'number',
        'eval' => [
            'rgxp' => 'natural'
        ],
        'sql' => "int(11) signed NULL"
    ]
];

$GLOBALS['TL_MODELS']['tl_catalog'] = CatalogModel::class;
$GLOBALS['TL_MODELS']['tl_watchlist'] = WatchlistModel::class;
$GLOBALS['TL_MODELS']['tl_catalog_data'] = CatalogDataModel::class;
$GLOBALS['TL_MODELS']['tl_catalog_field'] = CatalogFieldModel::class;
$GLOBALS['TL_MODELS']['tl_catalog_option'] = CatalogOptionModel::class;
$GLOBALS['TL_MODELS']['tl_catalog_palette'] = CatalogPaletteModel::class;
$GLOBALS['TL_MODELS']['tl_catalog_reactions'] = CatalogReactionsModel::class;
$GLOBALS['TL_MODELS']['tl_catalog_reactions_data'] = CatalogReactionsDataModel::class;

if (Mode::get() == 'BE') {
    $objCombiner = new Combiner();
    $objCombiner->add('/bundles/alnvcontaocatalogmanager/css/backend.scss');
    $GLOBALS['TL_CSS']['catalog-manager-backend-css'] = $objCombiner->getCombinedFile();
}