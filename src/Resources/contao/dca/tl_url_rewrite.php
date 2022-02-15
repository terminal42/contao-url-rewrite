<?php

use Contao\System;

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2021, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

$GLOBALS['TL_DCA']['tl_url_rewrite'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
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
            'mode' => 2,
            'fields' => ['name'],
            'flag' => 1,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label' => [
            'fields' => ['name'],
            'format' => '%s',
            'label_callback' => ['terminal42_url_rewrite.listener.rewrite_container', 'onGenerateLabel'],
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.gif',
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.gif',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['show'],
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
            'toggle' => [
                'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['toggle'],
                'attributes' => 'onclick="Backend.getScrollOffset();"',
                'haste_ajax_operation' => [
                    'field' => 'inactive',
                    'options' => [
                        ['value' => 0, 'icon' => 'visible.svg'],
                        ['value' => 1, 'icon' => 'invisible.svg'],
                    ],
                ],
            ],
            'qrCode' => [
                'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['qrCode'],
                'href' => 'key=qrCode',
                'icon' => 'bundles/terminal42urlrewrite/icon-qr.svg',
                'button_callback' => ['terminal42_url_rewrite.listener.rewrite_container', 'onQrCodeButtonCallback'],
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['type', 'responseCode'],
        'default' => '{name_legend},name,type,priority,comment,inactive',
        'basic' => '{name_legend},name,type,priority,comment,inactive;{request_legend},requestHosts,requestPath,requestRequirements;{response_legend},responseCode;{examples_legend},examples',
        'expert' => '{name_legend},name,type,priority,comment,inactive;{request_legend},requestHosts,requestPath,requestCondition;{response_legend},responseCode;{examples_legend},examples',
    ],

    // Subpalettes
    'subpalettes' => [
        'responseCode_301' => 'responseUri',
        'responseCode_302' => 'responseUri',
        'responseCode_303' => 'responseUri',
        'responseCode_307' => 'responseUri',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'name' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['name'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'flag' => 1,
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
            'save_callback' => [
                ['terminal42_url_rewrite.listener.rewrite_container', 'onNameSaveCallback'],
            ],
        ],
        'type' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['type'],
            'default' => 'basic',
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options' => ['basic', 'expert'],
            'reference' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['typeRef'],
            'eval' => ['submitOnChange' => true, 'helpwizard' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'priority' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['priority'],
            'default' => '0',
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => 12,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'default' => '0'],
            'save_callback' => [static function ($value) {
                if (!preg_match('/^-?\d+$/', $value)) {
                    throw new \RuntimeException($GLOBALS['TL_LANG']['ERR']['digit']);
                }

                return $value;
            }]
        ],
        'comment' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['comment'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'inactive' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['inactive'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'clr'],
            'sql' => ['type' => 'boolean', 'default' => 0],
            'save_callback' => [
                ['terminal42_url_rewrite.listener.rewrite_container', 'onInactiveSaveCallback'],
            ],
        ],
        'requestHosts' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['requestHosts'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'listWizard',
            'eval' => ['multiple' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'blob', 'notnull' => false],
        ],
        'requestPath' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['requestPath'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'long clr'],
            'sql' => ['type' => 'string', 'default' => ''],
        ],
        'requestRequirements' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['requestRequirements'],
            'exclude' => true,
            'inputType' => 'keyValueWizard',
            'eval' => ['decodeEntities' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'blob', 'notnull' => false],
            'save_callback' => [
                ['terminal42_url_rewrite.listener.rewrite_container', 'onRequestRequirementsSaveCallback'],
            ],
        ],
        'requestCondition' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['requestCondition'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'decodeEntities' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'string', 'default' => ''],
        ],
        'responseCode' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['responseCode'],
            'default' => 301,
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => 11,
            'inputType' => 'select',
            'options_callback' => ['terminal42_url_rewrite.listener.rewrite_container', 'getResponseCodes'],
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'unsigned' => true],
        ],
        'responseUri' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['responseUri'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => [
                'decodeEntities' => true,
                'dcaPicker' => true,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'tl_class' => 'clr wizard',
            ],
            'sql' => ['type' => 'text', 'notnull' => false],
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
