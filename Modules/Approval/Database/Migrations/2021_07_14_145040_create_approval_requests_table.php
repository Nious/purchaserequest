<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('requestable_type')->nullable();
            $table->unsignedBigInteger('requestable_id')->nullable();
            $table->foreignId('approval_type_id')->constrained('approval_types')->cascadeOnDelete();
            $table->foreignId('approval_rule_id')->nullable()->constrained('approval_rules')->nullOnDelete();
            $table->decimal('amount', 18, 2)->default(0);
            $table->unsignedInteger('current_level')->nullable();
            $table->enum('status', ['pending','approved','rejected','cancelled'])->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};