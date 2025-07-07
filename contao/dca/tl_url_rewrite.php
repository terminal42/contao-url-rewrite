<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;

$GLOBALS['TL_DCA']['tl_url_rewrite'] = [
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'onsubmit_callback' => [
            ['terminal42_url_rewrite.listener.rewrite_container', 'onRecordsModified'],
        ],
        'ondelete_callback' => [
            ['terminal42_url_rewrite.listener.rewrite_container', 'onRecordsModified'],
        ],
        'oncopy_callback' => [
            ['terminal42_url_rewrite.listener.rewrite_container', 'onRecordsModified'],
        ],
        'onrestore_callback' => [
            ['terminal42_url_rewrite.listener.rewrite_container', 'onRecordsModified'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => ['name'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label' => [
            'fields' => ['name'],
            'format' => '%s',
            'label_callback' => ['terminal42_url_rewrite.listener.rewrite_container', 'onGenerateLabel'],
        ],
        'operations' => [
            'edit',
            'copy',
            'delete',
            'show',
            'toggle',
            'qrCode' => [
                'href' => 'key=qrCode',
                'icon' => 'bundles/terminal42urlrewrite/icon-qr.svg',
                'button_callback' => ['terminal42_url_rewrite.listener.rewrite_container', 'onQrCodeButtonCallback'],
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '{name_legend},name,priority,inactive,comment;{request_legend},requestHosts,requestPath,requestRequirements,requestCondition;{response_legend},responseCode,conditionalResponseUri,responseUri,keepQueryParams;{examples_legend},examples',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'default' => 0, 'unsigned' => true],
        ],
        'name' => [
            'search' => true,
            'sorting' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
            'save_callback' => [
                ['terminal42_url_rewrite.listener.rewrite_container', 'onNameSaveCallback'],
            ],
        ],
        'priority' => [
            'default' => '0',
            'filter' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_DESC,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w25'],
            'sql' => ['type' => 'integer', 'default' => '0'],
            'save_callback' => [static function ($value) {
                if (!preg_match('/^-?\d+$/', (string) $value)) {
                    throw new RuntimeException($GLOBALS['TL_LANG']['ERR']['digit']);
                }

                return $value;
            }],
        ],
        'inactive' => [
            'reverseToggle' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w25 m12'],
            'sql' => ['type' => 'boolean', 'default' => 0],
            'save_callback' => [
                ['terminal42_url_rewrite.listener.rewrite_container', 'onInactiveSaveCallback'],
            ],
        ],
        'comment' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'clr'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'requestHosts' => [
            'filter' => true,
            'inputType' => 'listWizard',
            'eval' => ['multiple' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'blob', 'notnull' => false],
        ],
        'requestPath' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'long clr'],
            'sql' => ['type' => 'string', 'default' => ''],
        ],
        'requestRequirements' => [
            'inputType' => 'keyValueWizard',
            'eval' => ['decodeEntities' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'blob', 'notnull' => false],
            'save_callback' => [
                ['terminal42_url_rewrite.listener.rewrite_container', 'onRequestRequirementsSaveCallback'],
            ],
        ],
        'requestCondition' => [
            'inputType' => 'text',
            'eval' => ['decodeEntities' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'text', 'notnull' => false],
        ],
        'responseCode' => [
            'default' => 301,
            'filter' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_ASC,
            'inputType' => 'select',
            'options_callback' => ['terminal42_url_rewrite.listener.rewrite_container', 'getResponseCodes'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'unsigned' => true],
        ],
        'conditionalResponseUri' => [
            'inputType' => 'keyValueWizard',
            'eval' => ['decodeEntities' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'blob', 'notnull' => false],
        ],
        'responseUri' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => [
                'decodeEntities' => true,
                'dcaPicker' => true,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'tl_class' => 'wizard w75',
            ],
            'sql' => ['type' => 'text', 'notnull' => false],
        ],
        'keepQueryParams' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'w25 m12',
            ],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'examples' => [
            'input_field_callback' => ['terminal42_url_rewrite.listener.rewrite_container', 'generateExamples'],
        ],
    ],
];

/*
 * Remove the DCA if not allowed
 */
if (!System::getContainer()->getParameter('terminal42_url_rewrite.backend_management')) {
    unset($GLOBALS['TL_DCA']['tl_url_rewrite']);
}
