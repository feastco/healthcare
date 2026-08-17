<?php

namespace App\View\Components\header;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NotificationDropdown extends Component
{
    public function render(): View
    {
        return view('components.header.notification-dropdown');
    }
}
