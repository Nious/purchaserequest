<?php

// database/migrations/2025_04_25_000001_create_master_budget_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void  
    {
        Schema::create('master_budget', function (Blueprint $table) {
            $table->id();
            $table->string('no_budgeting')->unique();
            $table->date('tgl_penyusunan');
            $table->string('bulan'); // contoh: "Mei 2025"
            $table->date('periode_awal');
            $table->date('periode_akhir');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->decimal('grandtotal', 15, 2)->default(0);
            $table->enum('approval_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_budget');
    }
};


