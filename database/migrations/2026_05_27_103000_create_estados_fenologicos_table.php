<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_fenologicos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('codigo_bbch', 10)->nullable()->unique();
            $table->text('descripcion')->nullable();
            $table->tinyInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_fenologicos');
    }
};
