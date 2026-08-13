<?php

namespace App\Actions;

use App\Models\Patient;

class GeneratePatientIdentifierAction
{
    public function handle(): string
    {
        $year = now()->format('Y');
        $prefix = "PAT-{$year}-";

        $latest = Patient::query()
            ->where('identifier_pat', 'like', "{$prefix}%")
            ->orderByDesc('identifier_pat')
            ->value('identifier_pat');

        $sequence = $latest
            ? ((int) substr($latest, -6)) + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
