<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeviceDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('device_details', function (Blueprint $table) {
            $table->id();
            $table->string('macAddress')->unique()->index(); // Links to devices.macAddress
            $table->string('display_id')->nullable();
            $table->string('display_name')->nullable();
            $table->string('device_id')->nullable();
            $table->string('device_name')->nullable();
            $table->string('device_version')->nullable();
            $table->string('site_id')->nullable();
            $table->string('site_name')->nullable();
            $table->string('app_geometry')->nullable();
            $table->string('app_id')->nullable();
            $table->string('app_name')->nullable();
            $table->string('app_package')->nullable();
            $table->string('app_version')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->json('display_info')->nullable(); // Store masterip, index, screen, screens
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('device_details');
    }
}