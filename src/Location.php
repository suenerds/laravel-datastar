<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar;

class Location extends ExecuteScript
{
    public function __construct(string $uri)
    {
        parent::__construct(
            script: "setTimeout(() => window.location = '$uri')",
        );
    }
}
