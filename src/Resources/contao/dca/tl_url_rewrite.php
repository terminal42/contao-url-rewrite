<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
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
            'mode' => 1,
            'fields' => ['name'],
            'flag' => 1,
            'panelLayout' => 'filter;search,limit',
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
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['type', 'responseCode'],
        'default' => '{name_legend},name,type',
        'basic' => '{name_legend},name,type;{request_legend},requestHosts,requestPath,requestRequirements;{response_legend},responseCode;{examples_legend},examples',
        'expert' => '{name_legend},name,type;{request_legend},requestHosts,requestPath,requestCondition;{response_legend},responseCode;{examples_legend},examples',
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
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255],
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
            'sql' => ['type' => 'string', 'length' => 255],
        ],
        'requestHosts' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['requestHosts'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'listWizard',
            'eval' => ['tl_class' => 'clr'],
            'sql' => ['type' => 'blob'],
        ],
        'requestPath' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['requestPath'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'long clr'],
            'sql' => ['type' => 'string'],
        ],
        'requestRequirements' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['requestRequirements'],
            'exclude' => true,
            'inputType' => 'keyValueWizard',
            'eval' => ['decodeEntities' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'blob'],
        ],
        'requestCondition' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['requestCondition'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'decodeEntities' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'string'],
        ],
        'responseCode' => [
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['responseCode'],
            'default' => 301,
            'exclude' => true,
            'filter' => true,
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
                'rgxp' => 'url',
                'decodeEntities' => true,
                'dcaPicker' => true,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'tl_class' => 'clr wizard',
            ],
            'sql' => ['type' => 'string'],
        ],
        'examples' => [
            'input_field_callback' => ['terminal42_url_rewrite.listener.rewrite_container', 'generateExamples'],
        ],
    ],
];
