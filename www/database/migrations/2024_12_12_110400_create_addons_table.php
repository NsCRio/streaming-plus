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
        Schema::create('addons', function (Blueprint $table) {
            $table->increments('addon_id');
            $table->string('addon_md5')->nullable();
            $table->string('addon_name')->nullable();
            $table->string('addon_url')->nullable();
            $table->string('addon_endpoint')->nullable();
            $table->string('addon_host')->nullable();
            $table->string('addon_config')->nullable();
            $table->string('addon_manifest')->nullable();
            $table->string('addon_server_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('addons');
    }
};
