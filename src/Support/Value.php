<?php

declare(strict_types=1);

namespace Pollora\Nectar\Support;

use Stringable;
use UnexpectedValueException;

/**
 * Reads what the application hands the tools without a type: request input, configuration, discovered items
 * and objects of packages Nectar does not require.
 *
 * @internal
 */
final class Value
{
    /**
     * The text of a scalar or a stringable, empty for anything else.
     */
    public static function text(mixed $value): string
    {
        return is_scalar($value) || $value instanceof Stringable ? (string) $value : '';
    }

    public static function integer(mixed $value): int
    {
        return is_scalar($value) ? (int) $value : 0;
    }

    /**
     * What a method of an object answers, null when there is no such method to call.
     */
    public static function ask(mixed $target, string $method): mixed
    {
        $call = [$target, $method];

        return is_object($target) && is_callable($call) ? $call() : null;
    }

    /**
     * The attribute a discovered item declares.
     *
     * @throws UnexpectedValueException When the item gives none
     */
    public static function attribute(mixed $declared): object
    {
        $attribute = self::ask($declared, 'newInstance');

        return is_object($attribute) ? $attribute : throw new UnexpectedValueException('The discovered item declares no attribute.');
    }
}
