<?php

declare(strict_types=1);

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Xjoc\NotificationCenter\Http\Controllers\User\PreferenceController;

Route::prefix(config('notification-center.route_prefix').'/user')
    ->middleware(array_merge((array) config('notification-center.user_middleware'), [SubstituteBindings::class]))
    ->name('notification-center.user.')
    ->group(function (): void {
        Route::get('preferences', [PreferenceController::class, 'index'])->name('preferences.index');
        Route::put('preferences/{type}/{channel}', [PreferenceController::class, 'update'])->name('preferences.update');
    });
