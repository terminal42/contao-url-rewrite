<?php

declare(strict_types=1);

use Contao\System;

/*
 * Add the backend module if allowed.
 */

if (System::getContainer()->getParameter('terminal42_url_rewrite.backend_management')) {
    $GLOBALS['BE_MOD']['system']['url_rewrites'] = [
        'tables' => ['tl_url_rewrite'],
        'qrCode' => ['terminal42_url_rewrite.qr_code_controller', 'index'],
        'stylesheet' => ['bundles/terminal42urlrewrite/style.css'],
    ];
}
