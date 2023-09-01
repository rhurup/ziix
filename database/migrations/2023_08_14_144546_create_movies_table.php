<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("parent_id")->default(0);
            $table->unsignedBigInteger("folders_id")->default(0);
            $table->string("filename")->default("");
            $table->string("subfolder")->default("");
            $table->string("extension")->nullable();
            $table->string("hash", 255)->index()->unique("movie_hash");
            $table->tinyInteger("status")->default(0);
            $table->string("title")->index()->default("");
            $table->integer("type")->index()->default(0);
            $table->integer("season")->index()->default(0);
            $table->integer("episode")->index()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('movies_metas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("movies_id")->default(0);
            $table->tinyInteger("status")->default(0);
            $table->string("encoding")->nullable();
            $table->string("resolution")->nullable();
            $table->string("length")->nullable();
            $table->integer("year")->default(0);
            $table->integer("width")->default(0);
            $table->integer("height")->default(0);
            $table->longText("dataset")->nullable();
            $table->timestamps();
        });

        Schema::create('movies_tmdbs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("movies_id")->default(0);
            $table->tinyInteger("status")->default(0);
            $table->string("tmdb_id")->default("")->index();
            $table->string("original_title")->default("")->index();
            $table->date("release_date")->nullable();
            $table->string("genre_ids")->default("");
            $table->string("poster")->default("");
            $table->timestamps();
        });

        Schema::create('movies_subtitles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("movies_id")->default(0);
            $table->tinyInteger("status")->default(0);
            $table->string("language")->default("");
            $table->string("path")->default("")->index();
            $table->timestamps();
        });

        Schema::create('movies_imdbs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("movies_id")->default(0);
            $table->tinyInteger("status")->default(0);
            $table->string("imdb_id")->default("")->index();
            $table->timestamps();
        });

        Schema::create('movies_folders', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger("status")->default(0)->index();
            $table->string("path");
            $table->string("sha")->default("");
            $table->string("plex_key")->default("");
            $table->integer("type")->index();
            $table->timestamps();
        });

        Schema::create('movies_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("movies_id")->default(0)->index();
            $table->string("type", 255)->default("")->index();
            $table->tinyInteger("status")->default(0);
            $table->text("params")->nullable();
            $table->longText("success")->nullable();
            $table->longText("error")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
