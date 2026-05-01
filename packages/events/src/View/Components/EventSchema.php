<?php

declare(strict_types=1);

namespace Capell\Events\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EventSchema extends Component
{
    /** @param  array<string, mixed>  $schema */
    public function __construct(public array $schema) {}

    public function render(): View
    {
        return view('capell-events::components.event-schema');
    }
}
