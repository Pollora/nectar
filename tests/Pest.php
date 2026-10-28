<?php

declare(strict_types=1);

use Pollora\Nectar\Tests\TestCase;

// Loaded here, not by Composer's autoload-dev: PHPStan loads that autoload, then WordPress's stubs, and both declare these functions.
require_once __DIR__.'/Helpers/wordpress-stubs.php';

uses(TestCase::class)->in('Feature');
