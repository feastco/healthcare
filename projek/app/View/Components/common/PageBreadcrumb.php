<?php

namespace App\View\Components\common;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PageBreadcrumb extends Component
{
    public function __construct(public string $pageTitle = 'Page') {}

    public function render(): View
    {
        return view('components.common.page-breadcrumb', ['pageTitle' => $this->pageTitle]);
    }
}
