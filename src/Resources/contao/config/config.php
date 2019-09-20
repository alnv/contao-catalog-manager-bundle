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
        ]
    ]
]);

array_insert( $GLOBALS['FE_MOD'], 2, [

    'catalog-manager-bundle' => [

        'listing' => 'Alnv\ContaoCatalogManagerBundle\Modules\ListingModule',
        'master' => 'Alnv\ContaoCatalogManagerBundle\Modules\MasterModule'
    ]
]);

$GLOBALS['TL_HOOKS']['initializeSystem'][] = [ 'catalogmanager.hooks.initialize', 'initializeBackendModules' ];
$GLOBALS['TL_HOOKS']['initializeSystem'][] = [ 'catalogmanager.hooks.initialize', 'generateDataContainerArray' ];

$GLOBALS['TL_MODELS']['tl_catalog'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogModel';
$GLOBALS['TL_MODELS']['tl_catalog_field'] = 'Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel';