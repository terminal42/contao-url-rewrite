# URL Rewrite bundle for Contao Open Source CMS

The extension provides a new backend module for Contao that allows to set various URL rewrites. Behind the scenes
the rules are added as routes to the internal application router which allows to use all the features provided
by the Symfony Routing component.

## Installation

Install the bundle via Composer:

```
composer require terminal42/contao-url-rewrite
```

## Configuration

If you are running the Contao Managed Edition then the extension should work out of the box. For all the other systems
you have to additionally register the routing configuration in the config files:  

```yaml
# config/routing.yml
imports:
  - { resource: '@Terminal42UrlRewriteBundle/Resources/config/routing.yml' }
```

## Examples

1. Find address on Google Maps:

```
Path restriction: find/{address}
Response code: 303 See Other
Response URI: https://www.google.com/maps?q={address}
---
Result: domain.tld/find/Switzerland → https://www.google.com/maps?q=Switzerland
```

2. Redirect to the news entry:

```
Path restriction: news/{news}
Requirements: [news => \d+]
Response code: 301 Moved Permanently
Response URI: {{news_url::{news}|absolute}}
---
Result: domain.tld/news/123 → domain.tld/news-reader/foobar-123.html
Result: domain.tld/news/foobar → 404 Page Not Found
```

3. Rewrite legacy URLs with query string:

```
Path restriction: home.php
Request condition: context.getMethod() in ['GET'] and request.query.has('page')
Response code: 301 Moved Permanently
Response URI: {{link_url::{page}|absolute}}
---
Result: domain.tld/home.php?page=123 → domain.tld/foobar-123.html
```

## Resources

1. [Symfony Routing](https://symfony.com/doc/current/routing.html)
2. [Symfony Routing Component](https://symfony.com/doc/current/components/routing.html)
3. [How to Restrict Route Matching through Conditions](https://symfony.com/doc/current/routing/conditions.html)
4. [How to Define Route Requirements](https://symfony.com/doc/current/routing/requirements.html) 
