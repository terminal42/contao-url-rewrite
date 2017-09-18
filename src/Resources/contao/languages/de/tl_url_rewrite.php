<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

$GLOBALS['TL_LANG']['tl_url_rewrite']['name'] = ['Interne Bezeichnung', 'Hier können Sie eine interne Bezeichnung erfassen (nur im Backend ersichtlich).'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['type'] = ['Typ', 'Wählen Sie hier den Typen der Umleitung aus.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestHosts'] = ['Host-Einschränkung', 'Hier können Sie die Regel auf bestimmte Hosts einschränken.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestPath'] = ['Pfad-Einschränkung', 'Hier können Sie einen Pfad eingeben.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestRequirements'] = ['Extra-Anforderungen', 'Hier können Sie Extra-Anforderungen für die Regel definieren.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['requestCondition'] = ['Anfrage-Bedingung', 'Bitte geben Sie hier die Bedingung mittels der Symfony Expression Language ein (z.B. <em>context.getMethod() in [\'GET\'] and request.query.has(\'foo\')</em>).'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['responseCode'] = ['Antwort-Statuscode', 'Wählen Sie hier den gewünschten Antwort-Statuscode aus.'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['responseUri'] = ['Antwort-Umleitungs-URL', 'Hier können Sie die Umleitungs-URL eingeben. Sie können Insert-Tags, Routen-Attribute und Query-Parameter als Wildcards nutzen (z.B. <em>/foo/{bar}</em>). Wenn Sie absolute URLs generieren möchten, nutzen Sie das "absolute" Insert Tag Flag (bspw. <em>{{link_url::123|absolute}}</em>).'];

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_url_rewrite']['name_legend'] = 'Bezeichnung';
$GLOBALS['TL_LANG']['tl_url_rewrite']['request_legend'] = 'Anfrage-Zuordnung (Request matching)';
$GLOBALS['TL_LANG']['tl_url_rewrite']['response_legend'] = 'Anwort-Verarbeitung (Response processing)';
$GLOBALS['TL_LANG']['tl_url_rewrite']['examples_legend'] = 'Beispiele';

/*
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_url_rewrite']['new'] = ['Neue Regel', 'Eine neue Regel anlegen'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['show'] = ['Regel-Details', 'Details der Regel ID %s anzeigen'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['edit'] = ['Regel editieren', 'Regel ID %s editieren'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['copy'] = ['Regel duplizieren', 'Regel ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_url_rewrite']['delete'] = ['Regel löschen', 'Regel ID %s löschen'];

/*
 * Reference
 */
$GLOBALS['TL_LANG']['tl_url_rewrite']['typeRef'] = [
    'basic' => ['einfach', 'Erlaubt die Anfrage-Zuordnung auf Basis von einfachen Symfony Routing Funktionen.'],
    'expert' => ['komplex', 'Erlaubt die Anfrage-Zuordnung auf Basis der Symfony <a href="https://symfony.com/doc/current/components/expression_language.html" target="_blank">Expression Language</a>. Für weitere Informationen <a href="https://symfony.com/doc/current/routing/conditions.html" target="_blank">besuchen Sie bitte diese Seite</a>.'],
];

/**
 * Examples
 */
$GLOBALS['TL_LANG']['tl_url_rewrite']['examples'] = [
    ['Adresse auf Google Maps finden:', 'Typ: einfach
Pfad-Einschränkung: find/{address}
Antwort-Statuscode: 303 See Other
Antwort-Umleitungs-URL: https://www.google.com/maps?q={address}
---
Resultat: domain.tld/find/Schweiz → https://www.google.com/maps?q=Schweiz'],
    ['Zu einem bestimmten News-Artikel weiterleiten:', 'Typ: einfach
Pfad-Einschränkung: news/{news}
Extra-Anforderungen: [news => \d+]
Antwort-Statuscode: 301 Moved Permanently
Antwort-Umleitungs-URL: {{news_url::{news}|absolute}}
---
Resultat: domain.tld/news/123 → domain.tld/news-reader/foobar-123.html
Resultat: domain.tld/news/foobar → 404 Page Not Found
'],
    ['Alte URLs mit Query Parameter umschreiben:', 'Typ: komplex
Pfad-Einschränkung: home.php
Anfrage-Bedingung: context.getMethod() == \'GET\' and request.query.has(\'page\')
Antwort-Statuscode: 301 Moved Permanently
Antwort-Umleitungs-URL: {{link_url::{page}|absolute}}
---
Resultat: domain.tld/home.php?page=123 → domain.tld/foobar-123.html'],
];
