<?php

declare(strict_types=1);

namespace Pollora\Nectar\Console;

use Illuminate\Console\Command;

class StartCommand extends Command
{
    protected $signature = 'nectar:mcp';

    protected $description = 'Start the Pollora Nectar MCP server';

    public function handle(): int
    {
        return $this->call('mcp:start', ['handle' => 'pollora-nectar']);
    }
}
