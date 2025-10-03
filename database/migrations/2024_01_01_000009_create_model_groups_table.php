<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('model_groups', function (Blueprint $table) {
            $table->uuidMorphs('model');
            $table->foreignUuid('group_id')->constrained('groups')->onDelete('cascade');
            $table->timestamps();

            $table->primary(['model_type', 'model_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_groups');
    }
};
