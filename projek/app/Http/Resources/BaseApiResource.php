<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseApiResource extends JsonResource
{
    public static $wrap = 'data';
}
