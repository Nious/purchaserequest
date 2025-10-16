<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalRuleUsersTable extends Migration
{
    public function up()
    {
        Schema::create('approval_rule_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_rule_level_id')->constrained('approval_rule_levels')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['requester','approver'])->default('approver');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('approval_rule_users');
    }
}
