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

        Schema::create('movies', function (Blueprint $table) {
            $table->increments('movie_id');
            $table->string('movie_imdb_id')->nullable();
            $table->string('movie_tmdb_id')->nullable();
            $table->string('movie_jw_id')->nullable();
            $table->string('movie_sc_id')->nullable();
            $table->string('movie_title')->nullable();
            $table->string('movie_original_title')->nullable();
            $table->unsignedInteger('movie_year')->nullable();
            $table->date('movie_release_date')->nullable();
            $table->unsignedInteger('movie_duration_min')->nullable();
            $table->unsignedDouble('movie_rating')->nullable();
            $table->unsignedInteger('movie_min_age')->nullable();
            $table->text('movie_categories')->nullable();
            $table->longText('movie_logo_img')->nullable();
            $table->longText('movie_poster_img')->nullable();
            $table->longText('movie_folder_img')->nullable();
            $table->longText('movie_backdrop_img')->nullable();
            $table->longText('movie_banner_img')->nullable();
            $table->longText('movie_landscape_img')->nullable();

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
        Schema::dropIfExists('movies');
    }
};
