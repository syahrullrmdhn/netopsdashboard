<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up()
{
    Schema::table('noc_shift_assignments', function (Blueprint $table) {
        // 1) drop the FK constraint
        $table->dropForeign(['user_id']);

        // 2) make the column nullable
        $table->unsignedBigInteger('user_id')
              ->nullable()
              ->change();

        // 3) re-add the FK
        $table->foreign('user_id')
              ->references('id')
              ->on('users')
              ->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('noc_shift_assignments', function (Blueprint $table) {
            //
        });
    }
};
