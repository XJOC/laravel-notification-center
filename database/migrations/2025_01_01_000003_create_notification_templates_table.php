<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('notification_type_id')
                ->constrained('notification_types')
                ->cascadeOnDelete();
            $table->string('channel');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->unique(['notification_type_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
