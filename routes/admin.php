<?php

declare(strict_types=1);

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Xjoc\NotificationCenter\Http\Controllers\Admin\DispatchController;
use Xjoc\NotificationCenter\Http\Controllers\Admin\EventBindingController;
use Xjoc\NotificationCenter\Http\Controllers\Admin\SettingController;
use Xjoc\NotificationCenter\Http\Controllers\Admin\TemplateController;
use Xjoc\NotificationCenter\Http\Controllers\Admin\TypeController;

Route::prefix(config('notification-center.route_prefix').'/admin')
    ->middleware(array_merge((array) config('notification-center.admin_middleware'), [SubstituteBindings::class]))
    ->name('notification-center.admin.')
    ->group(function (): void {
        Route::get('types', [TypeController::class, 'index'])->name('types.index');
        Route::post('types', [TypeController::class, 'store'])->name('types.store');
        Route::patch('types/{type}', [TypeController::class, 'update'])->name('types.update');
        Route::post('types/{type}/dispatch', [DispatchController::class, 'store'])->name('types.dispatch');
        Route::get('types/{type}/templates', [TemplateController::class, 'index'])->name('types.templates.index');
        Route::put('types/{type}/templates/{channel}', [TemplateController::class, 'update'])->name('types.templates.update');
        Route::get('types/{type}/event-bindings', [EventBindingController::class, 'index'])->name('types.event-bindings.index');
        Route::post('types/{type}/event-bindings', [EventBindingController::class, 'store'])->name('types.event-bindings.store');
        Route::delete('event-bindings/{binding}', [EventBindingController::class, 'destroy'])->name('event-bindings.destroy');
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    });
