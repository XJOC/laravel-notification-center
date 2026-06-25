<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_event_bindings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('notification_type_id')
                ->constrained('notification_types')
                ->cascadeOnDelete();
            $table->string('event_class');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('event_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_event_bindings');
    }
};
