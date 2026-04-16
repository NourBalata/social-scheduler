<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('facebook_pages', function (Blueprint $table) {
        // 1. نحذف الـ Foreign Key أولاً (لأنه هو اللي مانع حذف الـ Index)
        $table->dropForeign(['user_id']);

        // 2. الآن نحذف الـ Unique Index اللي عامل المشكلة
        $table->dropUnique('facebook_pages_user_id_page_id_unique');

        // 3. نعيد بناء الـ Foreign Key مرة ثانية ولكن كـ Index عادي وليس Unique
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::table('facebook_pages', function (Blueprint $table) {
        $table->unique(['user_id', 'page_id']);
    });
}
};
