<?php

declare(strict_types=1);

use Laravel\Boost\Concerns\RendersBladeGuidelines;

function renderCoreGuidelines(): string
{
    $renderer = new class
    {
        use RendersBladeGuidelines;

        public function render(string $path): string
        {
            return $this->renderBladeFile($path, []);
        }
    };

    return $renderer->render(dirname(__DIR__, 3).'/resources/boost/guidelines/core.blade.php');
}

it('keeps Blade directives quoted in the text as text', function () {
    expect(renderCoreGuidelines())
        ->toContain('`@can`')
        ->toContain('#[Meta(showInRest: true)]')
        ->not->toContain('<?php');
});

it('points to the skills of typed meta, roles and Block Bindings', function () {
    expect(renderCoreGuidelines())
        ->toContain('pollora-typed-meta')
        ->toContain('pollora-roles')
        ->toContain('pollora-block-bindings');
});

it('names every skill after its directory, with a description', function (string $skill) {
    $content = (string) file_get_contents($skill.'/SKILL.md');

    expect($content)->toStartWith("---\nname: ".basename($skill)."\ndescription: ");
})->with(fn (): array => glob(dirname(__DIR__, 3).'/resources/boost/skills/*', GLOB_ONLYDIR) ?: []);
