<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTokensTable extends Migration
{
    public function up()
    {
        Schema::create('tokens', function (Blueprint $table) {
            $table->increments('id');

            $table->string('access_token');
            $table->string('scope');
            $table->string('user_id')->nullable();
            $table->string('team_name');
            $table->string('team_id')->unique();
            $table->string('incoming_webhook_url')->nullable();
            $table->string('incoming_webhook_channel')->nullable();
            $table->string('incoming_webhook_channel_id')->nullable();
            $table->string('incoming_webhook_configuration_url')->nullable();
            $table->string('bot_user_id')->nullable()->default(null);
            $table->string('bot_access_token')->nullable()->default(null);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tokens');
    }
}
