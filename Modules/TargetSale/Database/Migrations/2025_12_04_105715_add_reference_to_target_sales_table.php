<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('target_sales', function (Blueprint $table) {
            // Hanya tambahkan reference, karena description sudah ada
            $table->string('reference')->after('id')->nullable(); 
        });
    }

    public function down()
    {
        Schema::table('target_sales', function (Blueprint $table) {
            $table->dropColumn(['reference']);
        });
    }
};