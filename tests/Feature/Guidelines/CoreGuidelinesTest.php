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
    $guidelines = renderCoreGuidelines();

    expect($guidelines)
        ->toContain('`@can`')
        ->toContain('#[Meta(showInRest: true)]');
    expect(str_contains($guidelines, '<?php'))->toBeFalse();
});

it('points to the skills of typed meta, roles and Block Bindings', function () {
    expect(renderCoreGuidelines())
        ->toContain('pollora-typed-meta')
        ->toContain('pollora-roles')
        ->toContain('pollora-block-bindings');
});

it('lists only the hook generators the framework registers', function () {
    $guidelines = renderCoreGuidelines();

    expect($guidelines)
        ->toContain('pollora:make:action')
        ->toContain('pollora:make:filter');
    expect(str_contains($guidelines, 'pollora:make:hook'))->toBeFalse();
});

it('shows how an AJAX action is protected, and does not show one that is not', function () {
    $guidelines = renderCoreGuidelines();

    expect($guidelines)
        ->toContain("#[Ajax('save_note', nonce: true)]")
        ->toContain("#[Ajax('track_visit', access: AjaxAccess::GUEST, nonce: false)]")
        ->not->toContain("#[Ajax('load_more', access: AjaxAccess::ALL)]");
});

it('tells an agent to choose the nonce of every AJAX action, and what happens when it does not', function () {
    $guidelines = renderCoreGuidelines();

    expect($guidelines)
        ->toContain('Say how every `#[Ajax]` action is protected (pollora/ajax 1.2+)')
        ->toContain('`nonce: true` verifies a nonce')
        ->toContain('`nonce: false` turns it off on purpose')
        ->toContain('the next major version of `pollora/ajax` verifies a nonce by default')
        ->toContain('Never leave an action open to guests without deciding');
});

it('documents the nonce of an AJAX action in the REST and AJAX skill', function () {
    $skill = (string) file_get_contents(dirname(__DIR__, 3).'/resources/boost/skills/pollora-rest-api/SKILL.md');

    expect($skill)
        ->toContain("#[Ajax('subscribe', nonce: true)]")
        ->toContain('nonce: false')
        ->toContain('pollora_ajax_invalid_nonce')
        ->toContain('wp_create_nonce(\'subscribe\')')
        ->toContain('->requireNonce()')
        ->toContain('The next major version of `pollora/ajax` verifies a nonce by default')
        ->not->toContain("check_ajax_referer('newsletter')");
});

it('names every skill after its directory, with a description', function (string $skill) {
    $content = (string) file_get_contents($skill.'/SKILL.md');

    expect($content)->toStartWith("---\nname: ".basename($skill)."\ndescription: ");
})->with(fn (): array => glob(dirname(__DIR__, 3).'/resources/boost/skills/*', GLOB_ONLYDIR) ?: []);

it('documents the object cache commands and the SQLite option', function () {
    expect(renderCoreGuidelines())
        ->toContain('pollora:object-cache:enable')
        ->toContain('`:flush`')
        ->toContain('`--db=sqlite`')
        ->toContain('object-cache-package');
});
