<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMailPrefsToUsers extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'signature')) {
                $table->text('signature')->nullable();
            }
            if (! Schema::hasColumn('users', 'default_font')) {
                $table->string('default_font')->default('Poppins');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'signature')) {
                $table->dropColumn('signature');
            }
            if (Schema::hasColumn('users', 'default_font')) {
                $table->dropColumn('default_font');
            }
        });
    }
}
