<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

/*
 * The harness's preset covers WordPress's classes and functions and the tests.
 * The package builds on pollora/framework, which requires the illuminate/* components it uses:
 * when laravel/framework is installed (Testbench, a Pollora project) it replaces them, and the
 * analyser then attributes their classes to laravel/framework.
 */
return (require __DIR__.'/vendor/pollora/harness/presets/composer-dependency-analyser.php')
    ->ignoreErrorsOnPackage('laravel/framework', [ErrorType::SHADOW_DEPENDENCY]);
