<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_types', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('key')->unique();
            $table->string('name');
            $table->string('category');
            $table->json('supported_channels');
            $table->json('variables')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->string('created_by')->default('config');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_types');
    }
};
