<?php

declare(strict_types=1);

use Laravel\Boost\Concerns\RendersBladeGuidelines;

function renderFrameworkGuidelines(array $data = []): string
{
    $renderer = new class
    {
        use RendersBladeGuidelines;

        public function render(string $path, array $data): string
        {
            return $this->renderBladeFile($path, $data);
        }
    };

    return $renderer->render(dirname(__DIR__, 3).'/resources/boost/guidelines/framework.blade.php', $data);
}

it('gives a Pollora 12 project the 12.x text only', function () {
    expect(renderFrameworkGuidelines(['polloraMajor' => 12]))
        ->toContain('## Pollora 12.x Specifics')
        ->toContain('@theme(\'my-theme\')')
        ->not->toContain('@@')
        ->not->toContain('## Pollora 13.x Specifics');
});

it('gives a Pollora 13 project the 13.x text only', function () {
    expect(renderFrameworkGuidelines(['polloraMajor' => 13]))
        ->toContain('## Pollora 13.x Specifics')
        ->toContain('<code-snippet name="Custom template handler"')
        ->not->toContain('Pollora 12.x')
        ->not->toContain('@verbatim');
});

it('reads the installed framework when no major is given', function () {
    expect(renderFrameworkGuidelines())->toContain('## Pollora 13.x Specifics');
});
