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

        Schema::create('tv_series', function (Blueprint $table) {
            $table->increments('series_id');
            $table->string('series_imdb_id')->nullable();
            $table->string('series_tmdb_id')->nullable();
            $table->string('series_jw_id')->nullable();
            $table->string('series_sc_id')->nullable();
            $table->string('series_title')->nullable();
            $table->string('series_original_title')->nullable();
            $table->unsignedInteger('series_year')->nullable();
            $table->date('series_release_date')->nullable();
            $table->string('series_duration_min')->nullable();
            $table->unsignedDouble('series_rating')->nullable();
            $table->unsignedInteger('series_min_age')->nullable();
            $table->text('series_categories')->nullable();
            $table->longText('series_logo_img')->nullable();
            $table->longText('series_poster_img')->nullable();
            $table->longText('series_folder_img')->nullable();
            $table->longText('series_backdrop_img')->nullable();
            $table->longText('series_banner_img')->nullable();
            $table->longText('series_landscape_img')->nullable();

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
        Schema::dropIfExists('tv_series');
    }
};
