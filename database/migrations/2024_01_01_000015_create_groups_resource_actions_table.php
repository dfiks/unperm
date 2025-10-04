<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('groups_resource_actions', function (Blueprint $table) {
            $table->foreignUuid('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignUuid('resource_action_id')->constrained('resource_actions')->onDelete('cascade');
            $table->timestamps();

            $table->primary(['group_id', 'resource_action_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups_resource_actions');
    }
};

