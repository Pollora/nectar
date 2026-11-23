<?php

declare(strict_types=1);

namespace Pollora\Nectar\Tests\Helpers;

use BadMethodCallException;
use Illuminate\Support\Collection;
use Pollora\Discovery\Application\Services\DiscoveryManager;
use Pollora\Discovery\Domain\Contracts\DiscoveryEngineInterface;
use Pollora\Discovery\Domain\Contracts\DiscoveryInterface;
use Pollora\Discovery\Domain\Contracts\DiscoveryItemsInterface;
use Pollora\Discovery\Domain\Contracts\DiscoveryLocationInterface;
use Pollora\Discovery\Domain\Contracts\ReflectionCacheInterface;
use Pollora\Discovery\Domain\Models\DiscoveryItems;
use Spatie\StructureDiscoverer\Data\DiscoveredStructure;
use Throwable;

/**
 * A discovery engine written by hand, for tests of the code that reads a DiscoveryManager.
 *
 * The manager is final in recent versions of pollora/discovery, so tests build a real one over this engine instead of
 * mocking it. The engine answers only what the tools ask: the items of a discovery and the locations.
 */
final readonly class FakeDiscovery implements DiscoveryEngineInterface
{
    /**
     * @param  array<string, array<int, mixed>|Throwable>  $itemsByIdentifier  Items of each discovery; a Throwable is thrown when that discovery is read
     * @param  array<int, DiscoveryLocationInterface>  $locations  Locations the engine reports
     */
    public function __construct(
        private array $itemsByIdentifier = [],
        private array $locations = [],
    ) {}

    /**
     * Build a real DiscoveryManager over a fake engine.
     *
     * A discovery that is not listed is known and empty, like a discovery that found nothing.
     *
     * @param  array<string, array<int, mixed>|Throwable>  $itemsByIdentifier
     * @param  array<int, DiscoveryLocationInterface>  $locations
     */
    public static function manager(array $itemsByIdentifier = [], array $locations = []): DiscoveryManager
    {
        return new DiscoveryManager(new self($itemsByIdentifier, $locations));
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscovery(string $identifier): DiscoveryInterface
    {
        $items = $this->itemsByIdentifier[$identifier] ?? [];

        if ($items instanceof Throwable) {
            throw $items;
        }

        return new readonly class($identifier, new DiscoveryItems(['fake' => $items])) implements DiscoveryInterface
        {
            public function __construct(private string $identifier, private DiscoveryItemsInterface $items) {}

            public function discover(DiscoveryLocationInterface $location, DiscoveredStructure $structure, ?ReflectionCacheInterface $reflectionCache = null): void
            {
                throw new BadMethodCallException('The fake discovery does not scan.');
            }

            public function getItems(): DiscoveryItemsInterface
            {
                return $this->items;
            }

            public function setItems(DiscoveryItemsInterface $items): void
            {
                throw new BadMethodCallException('The fake discovery keeps the items it was given.');
            }

            public function apply(): void {}

            public function getIdentifier(): string
            {
                return $this->identifier;
            }
        };
    }

    /**
     * {@inheritDoc}
     */
    public function getLocations(): Collection
    {
        return collect($this->locations);
    }

    public function addLocation(DiscoveryLocationInterface $location): static
    {
        throw new BadMethodCallException('The fake engine is read only.');
    }

    public function addLocations(array $locations): static
    {
        throw new BadMethodCallException('The fake engine is read only.');
    }

    public function addDiscovery(string $identifier, string|DiscoveryInterface $discovery): static
    {
        throw new BadMethodCallException('The fake engine is read only.');
    }

    public function addDiscoveries(array $discoveries): static
    {
        throw new BadMethodCallException('The fake engine is read only.');
    }

    public function discover(): static
    {
        throw new BadMethodCallException('The fake engine is read only.');
    }

    public function apply(): static
    {
        throw new BadMethodCallException('The fake engine is read only.');
    }

    public function run(): static
    {
        throw new BadMethodCallException('The fake engine is read only.');
    }

    public function getDiscoveries(): Collection
    {
        throw new BadMethodCallException('The fake engine is read only.');
    }

    public function clearCache(): static
    {
        throw new BadMethodCallException('The fake engine is read only.');
    }

    public function clearLocations(): static
    {
        throw new BadMethodCallException('The fake engine is read only.');
    }

    public function runDiscovery(string $identifier, DiscoveryInterface $discovery): static
    {
        throw new BadMethodCallException('The fake engine is read only.');
    }
}
