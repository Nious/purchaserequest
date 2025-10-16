<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('budget_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_budget_id')->constrained('master_budget')->onDelete('cascade');
            $table->unsignedBigInteger('category_id'); // foreign ke categories
            $table->string('category_name'); 
            $table->decimal('budget', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_details');
    }
};
