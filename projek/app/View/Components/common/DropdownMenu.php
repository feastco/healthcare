<?php

namespace App\View\Components\common;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DropdownMenu extends Component
{
    public function __construct(public array $items = ['View More', 'Delete']) {}

    public function render(): View
    {
        return view('components.common.dropdown-menu', ['items' => $this->items]);
    }
}
