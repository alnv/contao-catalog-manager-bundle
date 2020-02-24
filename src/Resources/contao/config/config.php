<?php

define( "CATALOG_MANAGER_BUNDLE_VERSION", "2.0.0" );

array_insert( $GLOBALS['BE_MOD'], 2, [
    'catalog-manager-bundle' => [
        'catalog-manager' => [
            'name' => 'catalog-manager-bundle',
            'tables' => [
                'tl_catalog',
                'tl_catalog_field'
            ]
        ],
        'catalog-option' => [
            'name' => 'catalog-option-bundle',
            'tables' => [
                'tl_catalog_option'
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

$objCatalogAssetsManager = \Alnv\ContaoAssetsManagerBundle\Library\AssetsManager::getInstance();
$objCatalogAssetsManager->addIfNotExist( 'bundles/alnvcontaocatalogmanager/js/vue/components/view-listing-component.js' );
$objCatalogAssetsManager->addIfNotExist( 'bundles/alnvcontaocatalogmanager/js/vue/components/async-image-component.js' );
$objCatalogAssetsManager->addIfNotExist( 'bundles/alnvcontaocatalogmanager/js/vue/components/view-gmap-component.js' );

$GLOBALS['TL_HOOKS']['getPageLayout'][] = [ 'catalogmanager.hooks.pageLayout', 'generateMaster' ];
$GLOBALS['TL_HOOKS']['isVisibleElement'][] = [ 'catalogmanager.hooks.element', 'isVisibleElement' ];
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = [ 'catalogmanager.hooks.search', 'getSearchablePages' ];
$GLOBALS['TL_HOOKS']['generateBreadcrumb'][] = [ 'catalogmanager.hooks.breadcrumb', 'generateDetailPage' ];
$GLOBALS['TL_HOOKS']['initializeSystem'][] = [ 'catalogmanager.hooks.initialize', 'initializeBackendModules' ];
$GLOBALS['TL_HOOKS']['initializeSystem'][] = [ 'catalogmanager.hooks.initialize', 'generateDataContainerArray' ];
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = [ 'catalogmanager.hooks.datacontainer', 'generateDataContainerArray' ];
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [ 'Alnv\ContaoCatalogManagerBundle\Inserttags\ActiveInsertTag', 'replace' ];

$GLOBALS['TL_MODELS']['tl_catalog'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogModel';
$GLOBALS['TL_MODELS']['tl_catalog_field'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel';
$GLOBALS['TL_MODELS']['tl_catalog_option'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogOptionModel';

$GLOBALS['CM_MASTER'] = [];
$GLOBALS['CM_MODELS'] = [];
$GLOBALS['CM_CUSTOM_FIELDS'] = [];
$GLOBALS['CM_DATA_CONTAINERS'] = ['Table'];
$GLOBALS['CM_FIELDS'] = [ 'text', 'color', 'date', 'textarea', 'select', 'radio', 'checkbox', 'upload', 'empty'];

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
        'type' => 'string'
    ],
    'title' => [
        'group' => 'article',
        'type' => 'string'
    ],
    'member' => [
        'group' => 'member',
        'type' => 'id'
    ],
    'subtitle' => [
        'group' => 'article',
        'type' => 'string'
    ],
    'tags' => [
        'group' => 'article',
        'type' => 'array'
    ],
    'alias' => [
        'group' => 'article',
        'type' => 'string'
    ],
    'teaser' => [
        'group' => 'article',
        'type' => 'string'
    ],
    'content' => [
        'group' => 'article',
        'type' => 'string'
    ],
    'image' => [
        'group' => 'article',
        'type' => 'image'
    ],
    'hero' => [
        'group' => 'article',
        'type' => 'image'
    ],
    'duration' => [
        'group' => 'article',
        'type' => 'string'
    ],
    'price' => [
        'group' => 'shop',
        'type' => 'string'
    ],
    'category' => [
        'group' => 'article',
        'type' => 'string'
    ],
    'author' => [
        'group' => 'article',
        'type' => 'string'
    ],
    'location' => [
        'group' => 'address',
        'type' => 'string'
    ],
    'street' => [
        'group' => 'address',
        'type' => 'string'
    ],
    'streetNumber' => [
        'group' => 'address',
        'type' => 'string'
    ],
    'city' => [
        'group' => 'address',
        'type' => 'string'
    ],
    'zip' => [
        'group' => 'address',
        'type' => 'string'
    ],
    'state' => [
        'group' => 'address',
        'type' => 'string'
    ],
    'country' => [
        'group' => 'address',
        'type' => 'string'
    ],
    'firstname' => [
        'group' => 'person',
        'type' => 'string'
    ],
    'lastname' => [
        'group' => 'person',
        'type' => 'string'
    ],
    'email' => [
        'group' => 'contact',
        'type' => 'email'
    ],
    'url' => [
        'group' => 'contact',
        'type' => 'url'
    ],
    'phone' => [
        'group' => 'contact',
        'type' => 'string'
    ],
    'mobile' => [
        'group' => 'contact',
        'type' => 'string'
    ],
    'avatar' => [
        'group' => 'person',
        'type' => 'image'
    ],
    'company' => [
        'group' => 'contact',
        'type' => 'string'
    ],
    'startDate' => [
        'group' => 'date',
        'type' => 'date'
    ],
    'startTime' => [
        'group' => 'date',
        'type' => 'time'
    ],
    'endDate' => [
        'group' => 'date',
        'type' => 'date'
    ],
    'endTime' => [
        'group' => 'date',
        'type' => 'time'
    ],
    'date' => [
        'group' => 'date',
        'type' => 'date'
    ],
    'datim' => [
        'group' => 'date',
        'type' => 'datim'
    ],
    'time' => [
        'group' => 'date',
        'type' => 'time'
    ],
    'latitude' => [
        'group' => 'geo',
        'type' => 'float'
    ],
    'longitude'=> [
        'group' => 'geo',
        'type' => 'float'
    ],
    'gender' => [
        'group' => 'person',
        'type' => 'string'
    ],
    'sku' => [
        'group' => 'product',
        'type' => 'string'
    ]
];