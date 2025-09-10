<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOopsscreenToDevicesTable extends Migration
{
    public function up()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->boolean('oopsscreen')->nullable()->after('screenshot');
        });
    }

    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('oopsscreen');
        });
    }
}