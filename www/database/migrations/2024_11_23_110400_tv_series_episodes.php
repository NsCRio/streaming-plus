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

        Schema::create('tv_series_episodes', function (Blueprint $table) {
            $table->increments('episode_id');
            $table->unsignedInteger('episode_series_id')->nullable();
            $table->string('episode_imdb_id')->nullable();
            $table->string('episode_tmdb_id')->nullable();
            $table->string('episode_jw_id')->nullable();
            $table->string('episode_sc_id')->nullable();
            $table->string('episode_title')->nullable();
            $table->string('episode_original_title')->nullable();
            $table->unsignedInteger('episode_year')->nullable();
            $table->date('episode_release_date')->nullable();
            $table->string('episode_duration_min')->nullable();
            $table->unsignedDouble('episode_rating')->nullable();
            $table->unsignedInteger('episode_min_age')->nullable();
            $table->text('episode_categories')->nullable();
            $table->longText('episode_logo_img')->nullable();
            $table->longText('episode_poster_img')->nullable();
            $table->longText('episode_folder_img')->nullable();
            $table->longText('episode_backdrop_img')->nullable();
            $table->longText('episode_banner_img')->nullable();
            $table->longText('episode_landscape_img')->nullable();

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
        Schema::dropIfExists('tv_series_episodes');
    }
};
