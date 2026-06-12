<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Prompts\UpgradePolloraV13;

use Composer\InstalledVersions;
use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;

class UpgradePolloraV13 extends Prompt
{
    use RendersBladeGuidelines;

    protected string $name = 'upgrade-pollora-v13';

    protected string $title = 'upgrade_pollora_v13';

    protected string $description = 'Provides step-by-step guidance for upgrading from Pollora 12.x to 13.x, including all breaking changes and migration paths.';

    public function shouldRegister(): bool
    {
        $version = InstalledVersions::getPrettyVersion('pollora/framework');

        if ($version === null || str_starts_with($version, 'dev-')) {
            $version = InstalledVersions::getVersion('pollora/framework');
        }

        if ($version === null) {
            return false;
        }

        $version = ltrim($version, 'vV');

        return version_compare($version, '12.0.0', '>=')
            && version_compare($version, '13.0.0', '<');
    }

    public function handle(): Response
    {
        $content = $this->renderBladeFile(__DIR__.'/upgrade-pollora-v13.blade.php');

        return Response::text($content);
    }
}
