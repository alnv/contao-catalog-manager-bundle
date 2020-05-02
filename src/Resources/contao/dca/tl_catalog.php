<?php

$GLOBALS['TL_DCA']['tl_catalog'] = [
    'config' => [
        'dataContainer' => 'Table',
        'ctable' => [
            'tl_catalog_field'
        ],
        'onsubmit_callback' => [
            ['catalogmanager.datacontainer.catalog', 'generateModulename'],
            ['catalogmanager.datacontainer.catalog', 'createCustomFields']
        ],
        'ondelete_callback' => [
            ['catalogmanager.datacontainer.catalog', 'deleteTable']
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'table' => 'index',
                'module' => 'index'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 5,
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'fields' => ['name']
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'header.gif'
            ],
            'fields' => [
                'label' => $GLOBALS['TL_LANG']['tl_catalog']['fields'],
                'href' => 'table=tl_catalog_field',
                'icon' => 'edit.gif'
            ],
            'cut' => [
                'href' => 'act=paste&amp;mode=cut',
                'icon' => 'cut.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => ['\Alnv\ContaoCatalogManagerBundle\DataContainer\Catalog', 'getCutOperationButton']
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.gif'
            ]
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ]
    ],
    'palettes' => [
        '__selector__' => [ 'type', 'mode', 'sortingType' ],
        'default' => '{type_settings},type;',
        'catalog' => '{type_settings},type;{catalog_settings},table,dataContainer,validAliasCharacters;{general_settings},name,description;{mode_settings},mode;{extended_settings},enableCopy,enableVisibility,enablePanel,enableContentElements;{navigation_settings},navigation,position;{geocoding_settings:hide},enableGeocoding',
        'modifier' => '{type_settings},type;{general_settings},name;'
    ],
    'subpalettes' => [
        'sortingType_fixed' => 'flagField,flag',
        'sortingType_switchable' => 'order,flag',
        'mode_list' => 'sortingType,columns,showColumns',
        'mode_parent' => 'headerFields,sortingType,columns',
        'mode_tree'=> 'columns'
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true ]
        ],
        'pid' => [
            'sql' => ['type' => 'integer', 'notnull' => true, 'unsigned' => true, 'default' => 0 ]
        ],
        'sorting' => [
            'sql' => ['type' => 'integer', 'notnull' => true, 'unsigned' => true, 'default' => 0 ]
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'module' => [
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'name' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 128,
                'doNotCopy' => true,
                'tl_class' => 'w50',
                'decodeEntities' => true
            ],
            'search' => true,
            'sorting' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'description' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 120,
                'doNotCopy' => true,
                'tl_class' => 'w50',
                'decodeEntities' => true
            ],
            'search' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 120, 'default' => '']
        ],
        'type' => [
            'inputType' => 'select',
            'default' => 'catalog',
            'eval' => [
                'chosen' => true,
                'maxlength' => 32,
                'tl_class' => 'w50',
                'submitOnChange' => true,
                'blankOptionLabel' => '-',
                'includeBlankOption' => true
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getCatalogTypes' ],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog']['reference']['type'],
            'filter' => true,
            'exclude' => true,
            'sorting' => true,
            'sql' => ['type' => 'string', 'length' => 32, 'default' => '']
        ],
        'table' => [
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'extnd',
                'maxlength' => 128,
                'tl_class' => 'w50',
                'mandatory' => true,
                'doNotCopy' => true,
                'spaceToUnderscore' => true
            ],
            'save_callback' => [
                [ 'catalogmanager.datacontainer.catalog', 'watchTable' ]
            ],
            'search' => true,
            'sorting' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'dataContainer' => [
            'inputType' => 'select',
            'default' => 'Table',
            'eval' => [
                'chosen' => true,
                'maxlength' => 32,
                'tl_class' => 'w50',
                'mandatory' => true
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getDataContainers' ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 32, 'default' => 'Table']
        ],
        'validAliasCharacters' => [
            'inputType' => 'select',
            'default' => '0-9a-zA-Z',
            'eval' => [
                'includeBlankOption' => true,
                'decodeEntities' => true,
                'chosen' => true,
                'tl_class' => 'w50'
            ],
            'options_callback' => static function() {
                return \System::getContainer()->get('contao.slug.valid_characters')->getOptions();
            },
            'exclude' => true,
            'sql' => "varchar(255) NOT NULL default '0-9a-zA-Z'"
        ],
        'mode' => [
            'inputType' => 'select',
            'default' => 'list',
            'eval' => [
                'chosen' => true,
                'maxlength' => 16,
                'tl_class' => 'w50',
                'mandatory' => true,
                'submitOnChange' => true
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getModes' ],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog']['reference']['mode'],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 16, 'default' => 'list']
        ],
        'order' => [
            'inputType' => 'comboWizard',
            'eval' => [
                'tl_class' => 'w50',
                'mandatory' => true,
                'options2_callback' => [ 'catalogmanager.datacontainer.catalog', 'getOrderByStatements' ]
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFields' ],
            'sql' => ['type' => 'blob', 'notnull' => false ]
        ],
        'flag' => [
            'inputType' => 'select',
            'default' => '1',
            'eval' => [
                'chosen' => true,
                'maxlength' => 2,
                'tl_class' => 'w50',
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFlags' ],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog']['reference']['flag'],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 2, 'default' => '1']
        ],
        'flagField' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 128,
                'tl_class' => 'w50'
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFields' ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'headerFields' => [
            'inputType' => 'checkboxWizard',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => true
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getParentFields' ],
            'exclude' => true,
            'sql' => ['type' => 'blob', 'notnull' => false ]
        ],
        'showColumns' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],
        'sortingType' => [
            'inputType' => 'radio',
            'default' => 'fixed',
            'eval' => [
                'maxlength' => 16,
                'tl_class' => 'clr',
                'mandatory' => true,
                'submitOnChange' => true
            ],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog']['reference']['sortingType'],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getSortingTypes' ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 16, 'fixed' => true, 'default' => 'fixed']
        ],
        'columns' => [
            'inputType' => 'checkboxWizard',
            'default' => ['id'],
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => true
            ],
            'exclude' => true,
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFields' ],
            'sql' => [ 'type' => 'blob', 'notnull' => false  ]
        ],
        'navigation' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 32,
                'tl_class' => 'w50',
                'blankOptionLabel' => '-',
                'includeBlankOption' => true
            ],
            'exclude' => true,
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getNavigation' ],
            'sql' => ['type' => 'string', 'length' => 32, 'default' => '']
        ],
        'position' => [
            'inputType' => 'text',
            'default' => '0',
            'eval' => [
                'maxlength' => 4,
                'tl_class' => 'w50'
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 4, 'default' => '']
        ],
        'enableCopy' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],
        'enablePanel' => [
            'inputType' => 'checkbox',
            'default' => '1',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],
        'enableVisibility' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],
        'enableContentElements' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],
        'enableGeocoding' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ]
    ]
];