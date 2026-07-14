<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trends', function (Blueprint $table) {
            $table->id();
            $table->string('term');
            $table->string('normalized_term');
            $table->foreignId('region_id')->constrained();
            $table->foreignId('category_id')->nullable()->constrained();
            $table->string('period', 10);
            $table->unsignedInteger('rank');
            $table->unsignedBigInteger('search_volume')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['normalized_term', 'region_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trends');
    }
};
