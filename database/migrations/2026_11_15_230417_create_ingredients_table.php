<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('ingredients')->cascadeOnDelete();
            $table->string('type');
            $table->string('category_type')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('unit');
            $table->string('substitute_group')->nullable();
            $table->unsignedInteger('bottle_size_ml')->nullable();
            $table->timestamps();

            $table->index(['type', 'parent_id']);
            $table->index(['type', 'category_type']);
            $table->index('substitute_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
