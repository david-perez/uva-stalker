<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableStalks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('Stalks', function (Blueprint $table) {
            $table->increments('stalkID');
            $table->bigInteger('chat');
            $table->integer('uvaID');
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('deletedAt')->nullable();

            $table->foreign('chat')
                ->references('chatID')->on('Chats');
            $table->foreign('uvaID')
                ->references('uvaID')->on('UVaUsers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('Stalks');
    }
}
