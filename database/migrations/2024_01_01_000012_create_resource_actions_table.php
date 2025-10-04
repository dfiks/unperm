<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('resource_type');
            $table->string('resource_id');
            $table->string('action_type');
            $table->string('slug')->unique();
            $table->string('bitmask', 1000)->default('0');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['resource_type', 'resource_id']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_actions');
    }
};

