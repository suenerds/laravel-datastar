<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar\Tests\Fixtures;

use Illuminate\View\View;
use Suenerds\LaravelDatastar\IsPatchElementsEvent;

class DashboardUpdatedEvent
{
    use IsPatchElementsEvent;

    public function elements(): View
    {
        return view('chunk');
    }
}
