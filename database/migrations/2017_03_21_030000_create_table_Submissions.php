<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableSubmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('Submissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user');
            $table->text('name');
            $table->text('username');
            $table->text('problem');
            $table->text('verdict');
            $table->text('language');
            $table->integer('runtime');
            $table->integer('rank');
            $table->timestamp('time');

            $table->foreign('user')
                ->references('uvaID')->on('UVaUsers');
            $table->index('time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('Submissions');
    }
}
