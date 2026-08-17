<?php

namespace App\View\Components\header;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UserDropdown extends Component
{
    public function render(): View
    {
        return view('components.header.user-dropdown');
    }
}
