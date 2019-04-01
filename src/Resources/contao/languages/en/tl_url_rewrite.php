<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

$GLOBALS['TL_LANG']['tl_url_rewrite']['name'] = ['Internal name', 'Please enter the rewrite internal name (visible only in backend).'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['type'] = ['Type', 'Here you can choose the type.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['priority'] = ['Priority', 'Here you can define a priority sorted descending.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['inactive'] = ['Deactivate the rule', 'Deactivate the rewrite rule. It will not be loaded into the router configuration.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestHosts'] = ['Hosts restriction', 'Here you can restrict the rule to certain hosts.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestPath'] = ['Path restriction', 'Here you can enter the path to match.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestRequirements'] = ['Extra requirements', 'Here you can add extra requirements for the match.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestCondition'] = ['Request condition', 'Please enter the request condition using Symfony\'s Expression Language (e.g. <em>context.getMethod() in [\'GET\'] and request.query.has(\'foo\')</em>).'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['responseCode'] = ['Response status code', 'Here you can select the response status code.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['responseUri'] = ['Response redirect URL', 'Here you can enter the response redirect URI. You can use the insert tags, route attributes and query parameters as wildcards (e.g. <em>/foo/{bar}</em>). To generate absolute URLs using insert tags you can use the "absolute" insert tag flag (e.g. <em>{{link_url::123|absolute}}</em>).'];

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_url_rewrite']['name_legend'] = 'Rewrite name';
$GLOBALS['TL_LANG']['tl_url_rewrite']['request_legend'] = 'Request matching';
$GLOBALS['TL_LANG']['tl_url_rewrite']['response_legend'] = 'Response processing';
$GLOBALS['TL_LANG']['tl_url_rewrite']['examples_legend'] = 'Examples';

/*
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_url_rewrite']['new'] = ['New rule', 'Create a new rewrite rule'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['show'] = ['Rule details', 'Show the details of rewrite rule ID %s'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['edit'] = ['Edit rule', 'Edit rewrite rule ID %s'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['copy'] = ['Duplicate rule', 'Duplicate rewrite rule ID %s'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['delete'] = ['Delete rule', 'Delete rewrite rule ID %s'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['toggle'] = ['Activate/deactivate rule', 'Activate/deactivate rewrite rule ID %s'];

/*
 * Reference
 */
$GLOBALS['TL_LANG']['tl_url_rewrite']['typeRef'] = [
    'basic' => ['Basic', 'Allows to define the request matching using the basic Symfony routing features.'],
    'expert' => ['Expert', 'Allows to define the request condition using the <a href="https://symfony.com/doc/current/components/expression_language.html" target="_blank">Expression Language</a>. For more information please <a href="https://symfony.com/doc/current/routing/conditions.html" target="_blank">visit this link</a>.'],
];

/*
 * Examples
 */
$GLOBALS['TL_LANG']['tl_url_rewrite']['examples'] = [
    ['Find address on Google Maps:', 'Type: basic
Path restriction: find/{address}
Response code: 303 See Other
Response redirect URL: https://www.google.com/maps?q={address}
---
Result: domain.tld/find/Switzerland → https://www.google.com/maps?q=Switzerland'],
    ['Redirect to a specific news entry:', 'Type: basic
Path restriction: news/{news}
Requirements: [news => \d+]
Response code: 301 Moved Permanently
Response redirect URL: {{news_url::{news}|absolute}}
---
Result: domain.tld/news/123 → domain.tld/news-reader/foobar-123.html
Result: domain.tld/news/foobar → 404 Page Not Found
'],
    ['Rewrite legacy URLs with query string:', 'Type: expert
Path restriction: home.php
Request condition: context.getMethod() == \'GET\' and request.query.has(\'page\')
Response code: 301 Moved Permanently
Response redirect URL: {{link_url::{page}|absolute}}
---
Result: domain.tld/home.php?page=123 → domain.tld/foobar-123.html'],
];
