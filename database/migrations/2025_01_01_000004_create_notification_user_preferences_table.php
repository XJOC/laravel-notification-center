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
            // Explicit short index name: the auto-generated
            // notification_user_preferences_notifiable_type_notifiable_id_index
            // is 65 chars and exceeds MySQL's 64-char identifier limit.
            $table->morphs('notifiable', 'nup_notifiable_index');
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
