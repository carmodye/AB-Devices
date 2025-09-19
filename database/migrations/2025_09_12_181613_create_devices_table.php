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
            $table->string('client'); // e.g., "ta", "sheetz1"
            $table->string('operatingSystem')->nullable(); // e.g., "webOS"
            $table->string('macAddress')->nullable(); // e.g., "00A15923FB78", indexed for searching
            $table->string('model')->nullable(); // e.g., "55UH5J-HP"
            $table->string('firmwareVersion')->nullable(); // e.g., "03.72.30"
            $table->string('screenshot')->nullable(); // e.g., URL to image
            $table->string('oopsscreen')->nullable()->default(null); // String: "true", "false", "N/A", or null/blank
            $table->timestamp('lastreboot')->nullable(); // e.g., "2025-08-30T04:00:25.692Z"
            $table->bigInteger('unixepoch')->nullable()->index(); // e.g., 1693380025, indexed for searching
            $table->boolean('warning')->default(false);
            $table->boolean('error')->default(false);
            $table->timestamps();
            $table->index('client', 'devices_client_index');
            $table->index(['client', 'warning', 'error'], 'devices_client_warning_error_index');
            $table->index('macAddress', 'devices_macAddress_index');            
        });
    }

    public function down()
    {
        Schema::dropIfExists('devices');
    }
}