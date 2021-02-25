<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGeneralChannelIdToTokensTable extends Migration
{
    public function up()
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->string('general_channel_id', 100)->nullable()->after('team_id');
        });
    }

    public function down()
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->dropColumn('general_channel_id');
        });
    }
}
