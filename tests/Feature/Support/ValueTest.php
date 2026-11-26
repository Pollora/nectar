<?php

declare(strict_types=1);

use Pollora\Nectar\Support\Value;

/**
 * A target with a static method, callable from its class name alone.
 */
final class NectarValueTarget
{
    public static function answer(): string
    {
        return 'answered';
    }
}

it('reads the text of a scalar or a stringable, and nothing from anything else', function (mixed $value, string $expected): void {
    expect(Value::text($value))->toBe($expected);
})->with([
    'string' => ['daily', 'daily'],
    'integer' => [42, '42'],
    'stringable' => [new class implements Stringable
    {
        public function __toString(): string
        {
            return 'as text';
        }
    }, 'as text'],
    'null' => [null, ''],
    'array' => [['a'], ''],
    'plain object' => [new stdClass, ''],
]);

it('reads the integer of a scalar, and zero from anything else', function (mixed $value, int $expected): void {
    expect(Value::integer($value))->toBe($expected);
})->with([
    'numeric string' => ['25', 25],
    'float' => [2.9, 2],
    'null' => [null, 0],
    'array' => [['25'], 0],
    'plain object' => [new stdClass, 0],
]);

it('asks an object only for a method it has', function (): void {
    expect(Value::ask(new NectarValueTarget, 'answer'))->toBe('answered')
        ->and(Value::ask(new NectarValueTarget, 'missing'))->toBeNull();
});

it('asks nothing of a class name, even one with a callable static method', function (): void {
    expect(Value::ask(NectarValueTarget::class, 'answer'))->toBeNull();
});
