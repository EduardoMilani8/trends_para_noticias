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
        Schema::table('trend_articles', function (Blueprint $table) {
            $table->text('url')->change();
            $table->text('title')->change();
            $table->text('site_name')->change();
        });
    }

    public function down(): void
    {
        Schema::table('trend_articles', function (Blueprint $table) {
            $table->string('url')->change();
            $table->string('title')->change();
            $table->string('site_name')->change();
        });
    }
};
