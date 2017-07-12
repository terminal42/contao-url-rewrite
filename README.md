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
terminal42_url_rewrite:
    resource: .
    type: terminal42_url_rewrite
```
