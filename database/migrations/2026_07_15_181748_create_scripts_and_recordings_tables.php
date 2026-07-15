<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scripts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('script_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('line_index');
            $table->string('path');
            $table->timestamps();

            $table->unique(['script_id', 'line_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordings');
        Schema::dropIfExists('scripts');
    }
};
