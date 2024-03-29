<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2021, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

use Contao\System;

/*
 * Add the backend module if allowed.
 */

if (System::getContainer()->getParameter('terminal42_url_rewrite.backend_management')) {
    $GLOBALS['BE_MOD']['system']['url_rewrites'] = [
        'tables' => ['tl_url_rewrite'],
        'qrCode' => ['terminal42_url_rewrite.qr_code_controller', 'index'],
    ];
}

/*
 * Hooks
 */
$GLOBALS['TL_HOOKS']['insertTagFlags'][] = ['terminal42_url_rewrite.listener.insert_tags', 'onInsertTagFlags'];
