<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->dateTime('open_date');
            $table->unsignedBigInteger('customer_id');
            $table->string('issue_type', 255);
            $table->text('service_detail')->nullable();
            $table->integer('sla_duration')->unsigned();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->boolean('alert')->default(false);
            $table->unsignedBigInteger('user_id');
            $table->decimal('realtime_sla', 5, 2)->default(100);
            $table->text('problem_detail')->nullable();  // nullable so we don’t need DBAL
            $table->string('escalation')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tickets');
    }
}
