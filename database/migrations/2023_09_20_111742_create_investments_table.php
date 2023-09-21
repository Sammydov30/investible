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
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->string('investmentid')->nullable();
            $table->string('investor')->nullable();
            $table->string('nextofkin')->nullable();
            $table->string('account')->nullable();
            $table->string('planid')->nullable();
            $table->string('amountpaid')->nullable();
            $table->string('amount_to_be_returned')->nullable();
            $table->string('percentage')->nullable();
            $table->string('return')->nullable();
            $table->string('amountpaidsofar')->nullable();
            $table->string('agreementdate')->nullable();
            $table->string('timeduration')->nullable();
            $table->string('timeremaining')->nullable();
            $table->string('startdate')->nullable();
            $table->string('stopdate')->nullable();
            $table->string('period')->nullable();
            $table->mediumText('description')->nullable();
            $table->string('witnessname')->nullable();
            $table->string('witnessaddress')->nullable();
            $table->string('witnessphone')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
