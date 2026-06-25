<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_user_preferences', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->morphs('notifiable');
            $table->foreignId('notification_type_id')
                ->constrained('notification_types')
                ->cascadeOnDelete();
            $table->string('channel');
            $table->boolean('opted_out')->default(false);
            $table->timestamps();

            $table->unique(
                ['notifiable_type', 'notifiable_id', 'notification_type_id', 'channel'],
                'nup_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_user_preferences');
    }
};
