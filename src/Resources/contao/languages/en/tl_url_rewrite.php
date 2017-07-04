<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

$GLOBALS['TL_LANG']['tl_url_rewrite']['name'] = ['Internal name', 'Please enter the rewrite internal name (visible only in backend).'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestScheme'] = ['Scheme restriction', 'Here you can choose the scheme to match.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestHosts'] = ['Hosts restriction', 'Here you can restrict the match to certain hosts.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestPath'] = ['Path restriction', 'Here you can enter the path to match.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestRequirements'] = ['Extra requirements', 'Here you can add extra requirements for the match.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['responseCode'] = ['Response code', 'Here you can select the response status code.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['responseUri'] = ['Response URI', 'Here you can enter the response URI. You can use the insert tags and route attributes as wildcards (e.g. /foo/{bar})'];

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
