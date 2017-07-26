<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

$GLOBALS['TL_LANG']['tl_url_rewrite']['name'] = ['Internal name', 'Please enter the rewrite internal name (visible only in backend).'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['type'] = ['Type', 'Here you can choose enter the type.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestHosts'] = ['Hosts restriction', 'Here you can restrict the match to certain hosts.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestPath'] = ['Path restriction', 'Here you can enter the path to match.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestRequirements'] = ['Extra requirements', 'Here you can add extra requirements for the match.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestCondition'] = ['Request condition', 'Please enter the request condition in the expression language (e.g. <em>context.getMethod() in [\'GET\'] and request.query.has(\'foo\')</em>).'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['responseCode'] = ['Response code', 'Here you can select the response status code.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['responseUri'] = ['Response URI', 'Here you can enter the response URI. You can use the insert tags, route attributes and query parameters as wildcards (e.g. <em>/foo/{bar}</em>). To generate the absolute URLs using insert tags you can use the absolute flag (e.g. <em>{{link_url::123|absolute}}</em>).'];

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_url_rewrite']['name_legend'] = 'Rewrite name';
$GLOBALS['TL_LANG']['tl_url_rewrite']['request_legend'] = 'Request matching';
$GLOBALS['TL_LANG']['tl_url_rewrite']['response_legend'] = 'Response processing';

/*
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_url_rewrite']['new'] = ['New rewrite', 'Create a new rewrite'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['show'] = ['Rewrite details', 'Show the details of rewrite ID %s'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['edit'] = ['Edit rewrite', 'Edit rewrite ID %s'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['copy'] = ['Duplicate rewrite', 'Duplicate rewrite ID %s'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['delete'] = ['Delete rewrite', 'Delete rewrite ID %s'];

/*
 * Reference
 */
$GLOBALS['TL_LANG']['tl_url_rewrite']['error.responseUriAbsolute'] = 'The response URI must be an absolute path!';
$GLOBALS['TL_LANG']['tl_url_rewrite']['typeRef'] = [
    'basic' => ['Basic', 'Allows to define the request matching using the basic routing features.'],
    'expert' => ['Expert', 'Allows to define the request condition using the <a href="https://symfony.com/doc/current/components/expression_language.html" target="_blank">Expression Language</a>. For more informations please <a href="https://symfony.com/doc/current/routing/conditions.html" target="_blank">visit this link</a>.'],
];
