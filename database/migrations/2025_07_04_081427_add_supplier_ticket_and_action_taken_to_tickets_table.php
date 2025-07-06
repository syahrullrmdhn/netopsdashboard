<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSupplierTicketAndActionTakenToTicketsTable extends Migration
{
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('supplier_ticket_number')->nullable()->after('ticket_number');
            $table->text('action_taken')->nullable()->after('problem_detail');
        });
    }

    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['supplier_ticket_number', 'action_taken']);
        });
    }
}
