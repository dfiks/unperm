<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('model_roles', function (Blueprint $table) {
            $table->uuidMorphs('model');
            $table->foreignUuid('role_id')->constrained('roles')->onDelete('cascade');
            $table->timestamps();

            $table->primary(['model_type', 'model_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_roles');
    }
};
