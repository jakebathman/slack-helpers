<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
