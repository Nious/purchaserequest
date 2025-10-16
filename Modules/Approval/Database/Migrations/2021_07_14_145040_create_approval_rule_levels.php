<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalRuleLevelsTable extends Migration
{
    public function up()
    {
        Schema::create('approval_rule_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_rules_id')->constrained('approval_rules')->cascadeOnDelete();
            $table->unsignedInteger('level');
            $table->decimal('amount_limit', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('approval_rule_levels');
    }
}
