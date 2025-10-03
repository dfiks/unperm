<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('permission_bits', function (Blueprint $table) {
            $table->string('model_type'); // Action, Role, Group, или User
            $table->uuid('model_id');
            $table->unsignedBigInteger('bit_position'); // Позиция бита (0, 1, 2, ...)

            // Композитный первичный ключ
            $table->primary(['model_type', 'model_id', 'bit_position'], 'permission_bits_primary');

            // Индексы для быстрого поиска
            $table->index(['model_type', 'model_id'], 'permission_bits_model_index');
            $table->index('bit_position', 'permission_bits_position_index');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_bits');
    }
};
