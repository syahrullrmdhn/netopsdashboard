<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('supplier');
            $t->string('cid_supplier');
            $t->string('cid_customer');
            $t->string('service_type');
            $t->string('sales_order')->nullable();
            $t->string('sdn')->nullable();
            $t->text('topology')->nullable();
            $t->enum('status',['active','down','warning'])->default('active');
            $t->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('customers'); }
}
