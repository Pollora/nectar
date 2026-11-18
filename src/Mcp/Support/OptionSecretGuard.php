<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Support;

/**
 * Decides which WordPress options the `wp_option` MCP tool may show to an AI agent.
 *
 * Plugins keep API keys, tokens, passwords and salts in options; whatever the tool returns ends up
 * in the context of a model hosted by a third party. The guard therefore refuses, by default, any
 * option whose name looks like a secret, and masks the sub-keys that look like one inside the
 * array of an option that is returned.
 */
final readonly class OptionSecretGuard
{
    /** Placeholder shown instead of a value that looks like a secret. */
    public const string MASK = '[masqué]';

    /** A name that contains one of these words is treated as a secret (case-insensitive). */
    private const string SENSITIVE_PATTERN = '/(pass(word)?|secret|token|api[_-]?key|private[_-]?key|salt|auth|credential|license)/i';

    /**
     * Options that hold a secret although their name does not match the pattern.
     * WordPress's own security keys, and the recovery-mode keys.
     */
    private const array KNOWN_SENSITIVE = [
        'auth_key',
        'secure_auth_key',
        'logged_in_key',
        'nonce_key',
        'recovery_keys',
    ];

    /** @var list<string> Lower-cased option names the project explicitly allows. */
    private array $allowed;

    /**
     * @param  array<array-key, mixed>  $allowed  Exact option names to allow despite their name; anything that is not a non-empty string is ignored
     */
    public function __construct(array $allowed = [])
    {
        $this->allowed = array_values(array_unique(array_map(
            static fn (string $name): string => strtolower(trim($name)),
            array_filter($allowed, static fn (mixed $name): bool => is_string($name) && trim($name) !== ''),
        )));
    }

    /**
     * Whether the name looks like a secret and the project did not allow that exact name.
     */
    public function isDenied(string $key): bool
    {
        if (in_array(strtolower(trim($key)), $this->allowed, true)) {
            return false;
        }

        return $this->looksSensitive($key);
    }

    /**
     * Whether a name looks like a secret, without taking the allow-list into account.
     */
    public function looksSensitive(string $name): bool
    {
        return in_array(strtolower(trim($name)), self::KNOWN_SENSITIVE, true)
            || preg_match(self::SENSITIVE_PATTERN, $name) === 1;
    }

    /**
     * Replaces, at any depth, the value of every array key or object property that looks like a secret.
     *
     * @return mixed The value with its sensitive entries masked; scalars come back unchanged
     */
    public function redact(mixed $value): mixed
    {
        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        $redacted = [];

        foreach ($value as $name => $entry) {
            $redacted[$name] = $this->looksSensitive((string) $name) ? self::MASK : $this->redact($entry);
        }

        return $redacted;
    }

    /**
     * The message sent back to the agent when an option is refused.
     */
    public function denialMessage(string $key): string
    {
        return sprintf(
            'The option "%s" is not readable: its name looks like a secret (password, token, API key, salt, license...) and the answer would be sent to an AI model. '
            .'If it is safe to show, add that exact name to "wp_option.allow" in config/nectar.php.',
            $key,
        );
    }
}
