<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trend_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trend_id')->constrained();
            $table->string('url');
            $table->string('site_name');
            $table->string('title');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->timestamp('fetched_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trend_articles');
    }
};
