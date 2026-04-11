<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('facebook_pages', function (Blueprint $table) {
            $table->timestamp('token_expires_at')->nullable()->after('access_token');
        });
    }

    public function down()
    {
        Schema::table('facebook_pages', function (Blueprint $table) {
            $table->dropColumn('token_expires_at');
        });
    }
};
