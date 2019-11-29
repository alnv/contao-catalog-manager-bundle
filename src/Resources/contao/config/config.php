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
        'listing' => 'Alnv\ContaoCatalogManagerBundle\Modules\ListingModule',
        'master' => 'Alnv\ContaoCatalogManagerBundle\Modules\MasterModule'
    ]
]);

$objFormAssetsManager = \Alnv\ContaoAssetsManagerBundle\Library\AssetsManager::getInstance();
$objFormAssetsManager->addIfNotExist( 'bundles/alnvcontaocatalogmanager/js/vue/components/listing-component.js' );

$GLOBALS['TL_HOOKS']['isVisibleElement'][] = [ 'catalogmanager.hooks.element', 'isVisibleElement' ];
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = [ 'catalogmanager.hooks.search', 'getSearchablePages' ];
$GLOBALS['TL_HOOKS']['initializeSystem'][] = [ 'catalogmanager.hooks.initialize', 'initializeBackendModules' ];
$GLOBALS['TL_HOOKS']['initializeSystem'][] = [ 'catalogmanager.hooks.initialize', 'generateDataContainerArray' ];
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = [ 'catalogmanager.hooks.datacontainer', 'generateDataContainerArray' ];

$GLOBALS['TL_MODELS']['tl_catalog'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogModel';
$GLOBALS['TL_MODELS']['tl_catalog_field'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel';
$GLOBALS['TL_MODELS']['tl_catalog_option'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogOptionModel';

$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [ 'Alnv\ContaoCatalogManagerBundle\Inserttags\ActiveInsertTag', 'replace' ];

$GLOBALS['CM_MODELS'] = [];

$GLOBALS['CM_CUSTOM_FIELDS'] = [];

$GLOBALS['CM_DATA_CONTAINERS'] = ['Table'];

$GLOBALS['CM_OPERATORS'] = [
    'equal' => [],
    'notEqual' => [],
    'findInSet' => [],
    'regexp' => [],
    'greater' => [],
    'greaterEqual' => [],
    'lower' => [],
    'lowerEqual' => []
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