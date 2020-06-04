<?php

define( "CATALOG_MANAGER_BUNDLE_VERSION", "2.0.0" );

array_insert( $GLOBALS['BE_MOD'], 2, [
    'catalog-manager-bundle' => [
        'catalog-manager' => [
            'name' => 'catalog-manager-bundle',
            'tables' => [
                'tl_catalog',
                'tl_catalog_field',
                'tl_catalog_option'
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

$GLOBALS['TL_CTE']['includes']['component'] = 'Alnv\ContaoCatalogManagerBundle\Elements\ContentComponent';

$objCatalogAssetsManager = \Alnv\ContaoAssetsManagerBundle\Library\AssetsManager::getInstance();
$objCatalogAssetsManager->addIfNotExist( 'bundles/alnvcontaocatalogmanager/js/vue/components/view-listing-component.js' );
$objCatalogAssetsManager->addIfNotExist( 'bundles/alnvcontaocatalogmanager/js/vue/components/async-image-component.js' );
$objCatalogAssetsManager->addIfNotExist( 'bundles/alnvcontaocatalogmanager/js/vue/components/view-gmap-component.js' );

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
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['Alnv\ContaoCatalogManagerBundle\Inserttags\PageInsertTag', 'replace'];
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['Alnv\ContaoCatalogManagerBundle\Inserttags\ActiveInsertTag', 'replace'];
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['Alnv\ContaoCatalogManagerBundle\Inserttags\MasterInsertTag', 'replace'];
$GLOBALS['TL_HOOKS']['compileFormFields'][] = ['catalogmanager.hooks.formfields', 'compileFormFields'];
$GLOBALS['TL_HOOKS']['loadFormField'][] = ['catalogmanager.hooks.formfields', 'loadFormField'];

$GLOBALS['TL_MODELS']['tl_catalog'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogModel';
$GLOBALS['TL_MODELS']['tl_catalog_field'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel';
$GLOBALS['TL_MODELS']['tl_catalog_option'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogOptionModel';

$GLOBALS['CM_MASTER'] = [];
$GLOBALS['CM_MODELS'] = [];
$GLOBALS['CM_CUSTOM_FIELDS'] = [];
$GLOBALS['CM_DATA_CONTAINERS'] = ['Table'];
$GLOBALS['CM_FIELDS'] = [ 'text', 'color', 'date', 'textarea', 'select', 'radio', 'checkbox', 'pagepicker', 'upload', 'empty'];

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
    'reversedFindInSet' => [
        'token' => 'FIND_IN_SET(##value##,##field##)'
    ],
    'regexp' => [
        'token' => '##field## REGEXP ##value##'
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
    ]
];

$GLOBALS['CM_ROLES'] = [
    'miscellaneous' => [
        'group' => 'miscellaneous',
        'sql' => "blob NULL",
    ],
    'title' => [
        'group' => 'article',
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'headline' => [
        'group' => 'article',
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'page' => [
        'group' => 'article',
        'rgxp' => 'natural',
        'sql' => "int(10) unsigned NOT NULL default '0'"
    ],
    'member' => [
        'group' => 'member',
        'rgxp' => 'natural',
        'sql' => "int(10) unsigned NOT NULL default '0'"
    ],
    'group' => [
        'group' => 'member',
        'sql' => "blob NULL",
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
        'group' => 'article',
        'rgxp' => 'alias',
        'sql' => "varchar(255) NOT NULL default '%s'"
    ],
    'type' => [
        'group' => 'article',
        'sql' => "varchar(128) NOT NULL default '%s'"
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
        'sql' => "blob NULL"
    ],
    'company' => [
        'group' => 'contact',
        'sql' => "varchar(128) NOT NULL default '%s'"
    ],
    'startDate' => [
        'group' => 'date',
        'rgxp' => 'date',
        'sql' => "int(10) unsigned NULL"
    ],
    'startTime' => [
        'group' => 'date',
        'rgxp' => 'time',
        'sql' => "int(10) unsigned NULL"
    ],
    'endDate' => [
        'group' => 'date',
        'rgxp' => 'date',
        'sql' => "int(10) unsigned NULL"
    ],
    'endTime' => [
        'group' => 'date',
        'rgxp' => 'time',
        'sql' => "int(10) unsigned NULL"
    ],
    'date' => [
        'group' => 'date',
        'rgxp' => 'date',
        'sql' => "int(10) unsigned NULL"
    ],
    'datim' => [
        'group' => 'date',
        'rgxp' => 'datim',
        'sql' => "int(10) unsigned NULL"
    ],
    'time' => [
        'group' => 'date',
        'rgxp' => 'time',
        'sql' => "int(10) unsigned NULL"
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
    ],
    'pages' => [
        'group' => 'product',
        'sql' => "blob NULL"
    ]
];