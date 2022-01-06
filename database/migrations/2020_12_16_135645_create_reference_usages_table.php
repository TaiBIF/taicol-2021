<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferenceUsagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('reference_usages', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_taxon_name_id')->nullable()->comment('上階層');
            $table->unsignedInteger('reference_id');
            $table->unsignedInteger('taxon_name_id');
            $table->string('status')->comment('地位');
            $table->unsignedTinyInteger('is_title')->comment('是標題');
            $table->unsignedTinyInteger('is_indent')->comment('縮排');
            $table->unsignedTinyInteger('is_for_publish')->default(0)->comment('是發布 usage');
            $table->unsignedSmallInteger('group')->comment('同一組');

            $table->string('show_page', 10)->default('');
            $table->string('figure', 10)->default('');

            $table->text('name_remark')->comment('建議寫法');
            $table->text('custom_name_remark')->comment('建議寫法');

            $table->json('per_usages');
            $table->json('type_specimens')->comment('模式');
            $table->json('properties')->comment('其他(標註)');
            $table->unsignedTinyInteger('order');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('reference_id')->references('id')->on('references');
            $table->foreign('taxon_name_id')->references('id')->on('taxon_names');
            $table->foreign('parent_taxon_name_id')->references('id')->on('taxon_names');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reference_usages');
    }
}
