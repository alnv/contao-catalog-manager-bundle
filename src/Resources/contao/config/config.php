<?php

define("CATALOG_MANAGER_BUNDLE_VERSION", "2.1.11");

array_insert( $GLOBALS['BE_MOD'], 2, [
    'catalog-manager-bundle' => [
        'catalog-manager' => [
            'name' => 'catalog-manager-bundle',
            'tables' => [
                'tl_catalog',
                'tl_catalog_field',
                'tl_catalog_option',
                'tl_catalog_palette'
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
        ],
        'catalog-element' => [
            'name' => 'catalog-element-bundle',
            'tables' => [
                'tl_catalog_element',
                'tl_content'
            ]
        ]
    ]
]);

array_insert( $GLOBALS['FE_MOD'], 2, [
    'catalog-manager-bundle' => [
        'listing-map' => 'Alnv\ContaoCatalogManagerBundle\Modules\MapModule',
        'listing' => 'Alnv\ContaoCatalogManagerBundle\Modules\ListingModule',
        'master' => 'Alnv\ContaoCatalogManagerBundle\Modules\MasterModule'
    ]
]);

$GLOBALS['TL_CTE']['catalog-manager-bundle'] = [];
$GLOBALS['TL_CTE']['catalog-manager-bundle']['component'] = 'Alnv\ContaoCatalogManagerBundle\Elements\ContentComponent';
$GLOBALS['TL_CTE']['catalog-manager-bundle']['listview'] = 'Alnv\ContaoCatalogManagerBundle\Elements\ContentListView';

$GLOBALS['TL_HOOKS']['compileArticle'][] = ['catalogmanager.hooks.article', 'compileArticle'];
$GLOBALS['TL_HOOKS']['getPageLayout'][] = ['catalogmanager.hooks.pageLayout', 'generateMaster'];
$GLOBALS['TL_HOOKS']['isVisibleElement'][] = ['catalogmanager.hooks.element', 'isVisibleElement'];
$GLOBALS['TL_HOOKS']['compileFormField'][] = ['catalogmanager.hooks.widget','getAttributesFromDca'];
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = ['catalogmanager.hooks.search', 'getSearchablePages'];
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = ['catalogmanager.hooks.search', 'getSearchablePagesByPagesRoles'];
$GLOBALS['TL_HOOKS']['generateBreadcrumb'][] = ['catalogmanager.hooks.breadcrumb', 'generateDetailPage'];
$GLOBALS['TL_HOOKS']['initializeSystem'][] = ['catalogmanager.hooks.initialize', 'initializeBackendModules'];
$GLOBALS['TL_HOOKS']['initializeSystem'][] = ['catalogmanager.hooks.initialize', 'generateDataContainerArray'];
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = ['catalogmanager.hooks.datacontainer', 'generateDataContainerArray'];
$GLOBALS['TL_HOOKS']['compileFormFields'][] = ['catalogmanager.hooks.formfields', 'compileFormFields'];
$GLOBALS['TL_HOOKS']['loadFormField'][] = ['catalogmanager.hooks.formfields', 'loadFormField'];
$GLOBALS['TL_HOOKS']['sqlCompileCommands'][] = ['catalogmanager.hooks.sqlcompilecommands', 'execute'];

$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['Alnv\ContaoCatalogManagerBundle\Inserttags\PageInsertTag', 'replace'];
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['Alnv\ContaoCatalogManagerBundle\Inserttags\ActiveInsertTag', 'replace'];
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['Alnv\ContaoCatalogManagerBundle\Inserttags\WatchlistInsertTag', 'replace'];
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['Alnv\ContaoCatalogManagerBundle\Inserttags\MasterInsertTag', 'replace'];
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['Alnv\ContaoCatalogManagerBundle\Inserttags\Inserttags', 'replace'];

$GLOBALS['CM_MASTER'] = [];
$GLOBALS['CM_MODELS'] = [];
$GLOBALS['CM_CUSTOM_FIELDS'] = [];
$GLOBALS['CM_DATA_CONTAINERS'] = ['Table'];
$GLOBALS['CM_FIELDS'] = ['text', 'color', 'date', 'textarea', 'select', 'radio', 'checkbox', 'checkboxWizard', 'pagepicker', 'upload', 'explanation', 'empty', 'listWizard'];

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
        'token' => '##field## BETWEEN ##value## AND ##value##',
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
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'headline' => [
        'group' => 'article',
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'textfield' => [
        'group' => 'article',
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'page' => [
        'group' => 'article',
        'rgxp' => 'natural',
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
        'rgxp' => 'natural',
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
        'rgxp' => 'natural',
        'sql' => "int(10) unsigned NULL"
    ],
    'groups' => [
        'group' => 'member',
        'sql' => "blob NULL"
    ],
    'subtitle' => [
        'group' => 'article',
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'tags' => [
        'group' => 'article',
        'sql' => "blob NULL",
    ],
    'alias' => [
        'rgxp' => 'alias',
        'group' => 'article',
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
        'sql' => "longtext NULL"
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
        'rgxp' => 'alnum',
        'sql' => "varchar(64) NOT NULL default '%s'"
    ],
    'price' => [
        'group' => 'shop',
        'rgxp' => 'alnum',
        'sql' => "varchar(64) NOT NULL default '%s'"
    ],
    'vpe' => [
        'group' => 'shop',
        'rgxp' => 'alnum',
        'sql' => "varchar(64) NOT NULL default '%s'"
    ],
    'ean' => [
        'group' => 'shop',
        'rgxp' => 'alnum',
        'sql' => "varchar(64) NOT NULL default '%s'"
    ],
    'package' => [
        'group' => 'shop',
        'rgxp' => 'alnum',
        'sql' => "varchar(64) NOT NULL default '%s'"
    ],
    'category' => [
        'group' => 'article',
        'rgxp' => 'alnum',
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'author' => [
        'group' => 'article',
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'location' => [
        'group' => 'address',
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'street' => [
        'group' => 'address',
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'streetNumber' => [
        'group' => 'address',
        'sql' => "varchar(16) NOT NULL default '%s'"
    ],
    'city' => [
        'group' => 'address',
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'zip' => [
        'group' => 'address',
        'rgxp' => 'alnum',
        'sql' => "varchar(12) NOT NULL default '%s'"
    ],
    'state' => [
        'group' => 'address',
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'country' => [
        'group' => 'address',
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'fullname' => [
        'group' => 'person',
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'firstname' => [
        'group' => 'person',
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'lastname' => [
        'group' => 'person',
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'email' => [
        'group' => 'contact',
        'rgxp' => 'email',
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'emails' => [
        'group' => 'contact',
        'rgxp' => 'emails',
        'sql' => "text NULL"
    ],
    'url' => [
        'group' => 'contact',
        'rgxp' => 'url',
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'website' => [
        'group' => 'contact',
        'rgxp' => 'url',
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'phone' => [
        'group' => 'contact',
        'rgxp' => 'phone',
        'sql' => "varchar(32) NOT NULL default '%s'"
    ],
    'fax' => [
        'group' => 'contact',
        'rgxp' => 'phone',
        'sql' => "varchar(32) NOT NULL default '%s'"
    ],
    'mobile' => [
        'group' => 'contact',
        'rgxp' => 'phone',
        'sql' => "varchar(32) NOT NULL default '%s'"
    ],
    'avatar' => [
        'group' => 'person',
        'type' => 'image',
        'sql' => "blob NULL"
    ],
    'company' => [
        'group' => 'contact',
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'startDate' => [
        'group' => 'date',
        'rgxp' => 'date',
        'sql' => "int(11) signed NULL"
    ],
    'startTime' => [
        'group' => 'date',
        'rgxp' => 'time',
        'sql' => "int(11) signed NULL"
    ],
    'endDate' => [
        'group' => 'date',
        'rgxp' => 'date',
        'sql' => "int(11) signed NULL"
    ],
    'endTime' => [
        'group' => 'date',
        'rgxp' => 'time',
        'sql' => "int(11) signed NULL"
    ],
    'date' => [
        'group' => 'date',
        'rgxp' => 'date',
        'sql' => "int(11) signed NULL"
    ],
    'datim' => [
        'group' => 'date',
        'rgxp' => 'datim',
        'sql' => "int(11) signed NULL"
    ],
    'time' => [
        'group' => 'date',
        'rgxp' => 'time',
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
        'sql' => "decimal(10,8) NOT NULL default '0.000000'"
    ],
    'longitude'=> [
        'group' => 'geo',
        'sql' => "decimal(10,8) NOT NULL default '0.000000'"
    ],
    'gender' => [
        'group' => 'person',
        'sql' => "varchar(64) NOT NULL default '%s'"
    ],
    'sku' => [
        'group' => 'product',
        'sql' => "varchar(32) NOT NULL default '%s'"
    ],
    'decimal' => [
        'group' => 'number',
        'sql' => "decimal(10,8) NOT NULL default '0.000000'"
    ],
    'integer' => [
        'group' => 'number',
        'rgxp' => 'natural',
        'sql' => "int(10) unsigned NOT NULL default '0'"
    ]
];

$GLOBALS['TL_MODELS']['tl_catalog'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogModel';
$GLOBALS['TL_MODELS']['tl_watchlist'] = 'Alnv\ContaoCatalogManagerBundle\Models\WatchlistModel';
$GLOBALS['TL_MODELS']['tl_catalog_data'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogDataModel';
$GLOBALS['TL_MODELS']['tl_catalog_field'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel';
$GLOBALS['TL_MODELS']['tl_catalog_option'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogOptionModel';
$GLOBALS['TL_MODELS']['tl_catalog_palette'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogPaletteModel';
$GLOBALS['TL_MODELS']['tl_catalog_reactions'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogReactionsModel';
$GLOBALS['TL_MODELS']['tl_catalog_reactions_data'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogReactionsDataModel';

if (class_exists('Alnv\ContaoAssetsManagerBundle\Library\AssetsManager')) {
    $objCatalogAssetsManager = \Alnv\ContaoAssetsManagerBundle\Library\AssetsManager::getInstance();
    $objCatalogAssetsManager->addIfNotExist('bundles/alnvcontaocatalogmanager/js/vue/components/watchlist-form-component.js');
    $objCatalogAssetsManager->addIfNotExist('bundles/alnvcontaocatalogmanager/js/vue/components/view-listing-component.js');
    $objCatalogAssetsManager->addIfNotExist('bundles/alnvcontaocatalogmanager/js/vue/components/async-image-component.js');
    $objCatalogAssetsManager->addIfNotExist('bundles/alnvcontaocatalogmanager/js/vue/components/view-gmap-component.js');
}