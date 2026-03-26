<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('target_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('month');
            $table->year('year');
            $table->decimal('target_amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('status')->default('Pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('target_sales');
    }
};