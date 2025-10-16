<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApprovalRuleUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_rule_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_rules_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['1', '2', '3', '4']);
            $table->timestamps();

            // Foreign keys
            $table->foreign('approval_rules_id')
                  ->references('id')
                  ->on('approval_rules')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approval_rule_users');
    }
}
