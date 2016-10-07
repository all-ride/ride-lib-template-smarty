# Ride: Template Library (Smarty)

Smarty engine for the template library of the PHP Ride framework.

Read the documentation for [Smarty](http://www.smarty.net). 

## Code Sample

Check this code sample to see how to initialize this library:

```php
use ride\library\template\engine\SmartyEngine;
use ride\library\template\engine\SmartyResourceHandler;

function createSmartyTemplateEngine(System $system) {
    $resourceHandler = new SmartyResourceHandler($system->getFileBrowser(), 'view/smarty');
    $compileDirectory = $system->getFileSystem()->getFile('/path/to/compile/cache');
    $escapeHtml = true;
    
    $engine = new SmartyEngine($resourceHandler, $compileDirectory, $escape);
    $engine->addPublicDirectory('/path/to/plugins');
    
    return $engine;
}
```

### Implementations

You can check the related implementations of this library:
- [ride/app-template-smarty](https://github.com/all-ride/ride-app-template-smarty)
- [ride/lib-template](https://github.com/all-ride/ride-lib-template)
- [ride/web-template-smarty](https://github.com/all-ride/ride-web-template-smarty)
- [ride/web-template-smarty-asset](https://github.com/all-ride/ride-web-template-smarty-asset)
- [ride/web-template-smarty-minifier](https://github.com/all-ride/ride-web-template-smarty-minifier)

## Installation

You can use [Composer](http://getcomposer.org) to install this library.

```
composer require ride/lib-template-smarty
```
