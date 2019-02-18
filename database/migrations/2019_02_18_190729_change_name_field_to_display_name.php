<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeNameFieldToDisplayName extends Migration
{
    public function up()
    {
        Schema::table('slack_users', function (Blueprint $table) {
            $table->renameColumn('name', 'display_name');
        });
    }

    public function down()
    {
        Schema::table('slack_users', function (Blueprint $table) {
            $table->renameColumn('display_name', 'name');
        });
    }
}
