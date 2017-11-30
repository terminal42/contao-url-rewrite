# URL Rewrite bundle for Contao Open Source CMS


[![](https://img.shields.io/travis/terminal42/contao-url-rewrite/master.svg?style=flat-square)](https://travis-ci.org/terminal42/contao-url-rewrite/)
[![](https://img.shields.io/coveralls/terminal42/contao-url-rewrite/master.svg?style=flat-square)](https://coveralls.io/github/terminal42/contao-url-rewrite)

The extension provides a new way for Contao to set various URL rewrites. The available config providers are:

- Bundle config provider – the entries are taken from `config.yml` file
- Database provider – the entries are taken from backend module

Behind the scenes the rules are added as routes to the internal application router which allows to use all the features 
provided by the Symfony Routing component.

## Installation

Install the bundle via Composer:

```
composer require terminal42/contao-url-rewrite
```

## Configuration

### Bundle configuration

The bundle configuration is optional. Here you can define the entries and disable the backend management module.

```yaml
# config/config.yml
terminal42_url_rewrite:
    backend_management: false # Enable backend management of entries (true by default)
    entries: # Optional entries
        -
            request: { path: 'find/{address}' }
            response: { code: 303, uri: 'https://www.google.com/maps?q={address}' }

        -
            request:
                path: 'news/{news}'
                requirements: {news: '\d+'}
            response: 
                code: 301 
                uri: '{{news_url::{news}|absolute}}'

        -
            request:
                path: 'home.php'
                hosts: ['localhost']
                condition: "context.getMethod() == 'GET' and request.query.has('page')"
            response:
                uri: '{{link_url::{page}|absolute}}'
```

### Running under non Contao managed edition 

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
Type: basic
Path restriction: find/{address}
Response code: 303 See Other
Response URI: https://www.google.com/maps?q={address}
---
Result: domain.tld/find/Switzerland → https://www.google.com/maps?q=Switzerland
```

2. Redirect to a specific news entry:

```
Type: basic
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
Type: expert
Path restriction: home.php
Request condition: context.getMethod() == 'GET' and request.query.has('page')
Response code: 301 Moved Permanently
Response URI: {{link_url::{page}|absolute}}
---
Result: domain.tld/home.php?page=123 → domain.tld/foobar-123.html
```

## Create a custom config provider

In addition to the existing providers you can create your own class that provides the rewrite configurations.
The new service must extend the [Terminal42\UrlRewriteBundle\ConfigProvider\ConfigProviderInterface](src/ConfigProvider/ConfigProviderInterface.php) 
interface and be registered with the appropriate tag: 

```yaml
services:
    app.my_rewrite_provider:
        class: AppBundle\RewriteProvider\MyRewriteProvider
        public: false
        tags:
            - { name: terminal42_url_rewrite.provider, priority: 128 }
```

## Resources

1. [Symfony Routing](https://symfony.com/doc/current/routing.html)
2. [Symfony Routing Component](https://symfony.com/doc/current/components/routing.html)
3. [How to Restrict Route Matching through Conditions](https://symfony.com/doc/current/routing/conditions.html)
4. [How to Define Route Requirements](https://symfony.com/doc/current/routing/requirements.html) 
