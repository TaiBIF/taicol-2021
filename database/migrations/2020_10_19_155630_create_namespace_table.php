<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNamespaceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('my_namespaces', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('my_namespace_usages', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_taxon_name_id')->nullable()->comment('上階層');
            $table->unsignedInteger('namespace_id');
            $table->unsignedInteger('taxon_name_id');
            $table->string('status')->comment('地位');
            $table->unsignedTinyInteger('is_title')->comment('是標題');
            $table->unsignedTinyInteger('is_indent')->comment('縮排');
            $table->unsignedTinyInteger('is_for_publish')->default(0)->comment('是發佈 usage');
            $table->unsignedSmallInteger('group')->default(0)->comment('同一組會有相同 id');

            $table->string('show_page', 10)->default('');
            $table->string('figure', 10)->default('');
            $table->unsignedSmallInteger('order');

            $table->text('name_remark')->comment('建議寫法');
            $table->text('custom_name_remark')->comment('建議寫法');

            $table->json('per_usages');
            $table->json('type_specimens')->comment('模式');
            $table->json('properties')->comment('其他(標註)');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('namespace_id')->references('id')->on('my_namespaces');
            $table->foreign('taxon_name_id')->references('id')->on('taxon_names');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('my_namespaces');
        Schema::dropIfExists('my_namespace_usages');
    }
}
