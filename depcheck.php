<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->ignoreErrorsOnExtension('ext-pdo', [ErrorType::SHADOW_DEPENDENCY]) // Optional - only catching exceptions
    ->ignoreErrorsOnExtension('ext-zend-opcache', [ErrorType::SHADOW_DEPENDENCY]) // Optional - wrapped in function_exists()
    ->ignoreErrorsOnPackage('symfony-cmf/routing', [ErrorType::SHADOW_DEPENDENCY]) // Optional - wrapped in instanceof
;
