<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyFavoritesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('favorite_folders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->softDeletes();
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('favorite_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('favorite_folder_id');
            $table->unsignedBigInteger('collectable_id')->comment('1: Usage 2: 學名, 3:文獻');
            $table->unsignedBigInteger('collectable_type');
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('favorite_folder_id')->references('id')->on('favorite_folders');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('favorite_items');
        Schema::dropIfExists('favorite_folders');
    }
}
