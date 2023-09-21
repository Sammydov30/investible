<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullabe();
            $table->string('amount')->nullabe();
            $table->string('percentage')->nullabe();
            $table->string('no_of')->nullabe();
            $table->string('duration')->nullabe();
            $table->string('category')->nullabe();
            $table->text('description')->nullabe();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
