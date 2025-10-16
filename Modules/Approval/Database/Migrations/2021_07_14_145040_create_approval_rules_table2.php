
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApprovalRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_types_id'); // FK
            $table->integer('level');
            $table->decimal('amount_limit', 18, 2)->nullable();
            $table->string('approval_name', 255);
            $table->tinyInteger('is_active')->default(0); // 1 aktif, 0 tidak
            $table->timestamps();

            // Foreign key
            $table->foreign('approval_types_id')
                  ->references('id')
                  ->on('approval_types')
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
        Schema::dropIfExists('approval_rules');
    }
}
