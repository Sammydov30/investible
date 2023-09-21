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
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();
            $table->string('transfercode')->nullable();
            $table->string('investmentid')->nullable();
            $table->string('investorid')->nullable();
            $table->string('accountnumber')->nullable();
            $table->string('accountname')->nullable();
            $table->string('bankcode')->nullable();
            $table->string('narration')->nullable();
            $table->string('pdate')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
    }
};
