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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('firstname')->nullabe();
            $table->string('lastname')->nullabe();
            $table->string('phone')->nullabe();
            $table->string('username')->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->string('otp')->nullable();
            $table->string('expiration')->nullabe();
            $table->string('role')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->nullabe();
            $table->string('lastlogin')->nullabe();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
