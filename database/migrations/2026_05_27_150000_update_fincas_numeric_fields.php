<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE fincas ALTER COLUMN poligono TYPE smallint USING poligono::smallint');
        DB::statement('ALTER TABLE fincas ALTER COLUMN municipio_ine TYPE smallint USING municipio_ine::smallint');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE fincas ALTER COLUMN poligono TYPE varchar(20) USING poligono::varchar');
        DB::statement('ALTER TABLE fincas ALTER COLUMN municipio_ine TYPE char(3) USING municipio_ine::char');
    }
};
