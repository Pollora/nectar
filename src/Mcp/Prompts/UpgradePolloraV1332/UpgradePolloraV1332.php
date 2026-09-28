<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Prompts\UpgradePolloraV1332;

use Composer\InstalledVersions;
use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;

class UpgradePolloraV1332 extends Prompt
{
    use RendersBladeGuidelines;

    protected string $name = 'upgrade-pollora-v13-32';

    protected string $title = 'upgrade_pollora_v13_32';

    protected string $description = 'Provides step-by-step guidance for upgrading from Pollora 13.x (before 13.32) to 13.32, including the breaking changes, the skeleton files to update and the theme changes.';

    public function shouldRegister(): bool
    {
        $version = $this->installedFrameworkVersion();

        return $version !== null && $this->appliesTo($version);
    }

    /**
     * Whether a framework version is one this upgrade starts from: 13.0 up to,
     * not including, the first 13.32 pre-release.
     */
    public function appliesTo(string $version): bool
    {
        $version = ltrim($version, 'vV');

        return version_compare($version, '13.0.0', '>=')
            && version_compare($version, '13.32.0-dev', '<');
    }

    public function handle(): Response
    {
        $content = $this->renderBladeFile(__DIR__.'/upgrade-pollora-v13-32.blade.php');

        return Response::text($content);
    }

    private function installedFrameworkVersion(): ?string
    {
        $version = InstalledVersions::getPrettyVersion('pollora/framework');

        if ($version === null || str_starts_with($version, 'dev-')) {
            $version = InstalledVersions::getVersion('pollora/framework');
        }

        return $version;
    }
}
