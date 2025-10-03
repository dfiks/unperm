<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('groups_roles', function (Blueprint $table) {
            $table->foreignUuid('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignUuid('role_id')->constrained('roles')->onDelete('cascade');
            $table->timestamps();

            $table->primary(['group_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups_roles');
    }
};
