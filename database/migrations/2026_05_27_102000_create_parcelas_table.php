<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->constrained('fincas')->cascadeOnDelete();
            $table->foreignId('variedad_id')->nullable()->constrained('variedades')->nullOnDelete();
            $table->string('nombre', 255);
            $table->decimal('superficie_ha', 8, 4);
            $table->smallInteger('año_plantacion')->nullable();
            $table->string('sistema_conduccion', 100)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('finca_id');
            $table->index('variedad_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcelas');
    }
};
