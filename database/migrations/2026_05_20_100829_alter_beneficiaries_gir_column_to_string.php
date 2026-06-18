<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // GIR was a nullable integer (1–6, the AGGIR scale). Converting to
        // varchar(20) lets structures store custom autonomy-level keys
        // (e.g. 'non_evalue') alongside the standard '1'–'6' string values.
        // USING gir::text preserves existing data without loss.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE beneficiaries ALTER COLUMN gir TYPE varchar(20) USING gir::text'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE beneficiaries
                 ALTER COLUMN gir TYPE integer
                 USING CASE WHEN gir ~ '^[0-9]+$' THEN gir::integer ELSE NULL END"
            );
        }
    }
};
