<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        DB::statement(
            'ALTER TABLE appointments ADD CONSTRAINT appointments_starts_before_ends CHECK (starts_at < ends_at)'
        );

        DB::statement(
            'ALTER TABLE appointments ADD CONSTRAINT appointments_doctor_no_overlap '
            .'EXCLUDE USING gist (doctor_id WITH =, tsrange(starts_at, ends_at) WITH &&)'
        );

        Schema::table('appointments', function (Blueprint $table) {
            $table->index('starts_at');
            $table->index('ends_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_starts_at_index');
            $table->dropIndex('appointments_ends_at_index');
            $table->dropIndex('appointments_status_index');
        });

        DB::statement('ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_doctor_no_overlap');
        DB::statement('ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_starts_before_ends');
        DB::statement('DROP EXTENSION IF EXISTS btree_gist');
    }
};
