<?php

namespace App\View\Components\common;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Preloader extends Component
{
    public function render(): View
    {
        return view('components.common.preloader');
    }
}
