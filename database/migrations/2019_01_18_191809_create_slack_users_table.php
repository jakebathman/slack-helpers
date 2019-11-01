<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSlackUsersTable extends Migration
{
    public function up()
    {
        Schema::create('slack_users', function (Blueprint $table) {
            $table->increments('id');

            $table->string('slack_id')->unique();
            $table->string('team_id');
            $table->string('name');
            $table->string('color')->nullable();
            $table->string('real_name');
            $table->string('tz')->nullable()->default('America/Chicago');
            $table->string('updated');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('slack_users');
    }
}
