<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar\Tests\Fixtures;

use Suenerds\LaravelDatastar\IsPatchElementsEvent;

class OrderShippedEvent
{
    use IsPatchElementsEvent;

    public function elements(): string
    {
        return "<div>shipped</div>\n<div>notified</div>";
    }
}
