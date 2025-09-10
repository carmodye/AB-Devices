<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevicesTable extends Migration
{
    public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('client');
            $table->string('operatingSystem')->nullable();
            $table->string('macAddress')->nullable()->index(); // Index for faster searches
            $table->string('model')->nullable();
            $table->string('firmwareVersion')->nullable();
            $table->string('screenshot')->nullable();
            $table->timestamp('lastreboot')->nullable();
            $table->bigInteger('unixepoch')->nullable()->index(); // Index for sorting
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('devices');
    }
}