<?php

use App\Http\Controllers\Modules\ModuleDashboardController;
use App\Http\Controllers\Modules\ModuleEntityController;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Route;

$moduleKey = 'mijloace-fixe';
$module = ModuleRegistry::module($moduleKey);

Route::prefix($module['route_prefix'])->name("modules.$moduleKey.")->group(function () use ($moduleKey, $module) {
    Route::get('panou', [ModuleDashboardController::class, 'show'])
        ->defaults('moduleKey', $moduleKey)
        ->name('dashboard');

    foreach (($module['entities'] ?? []) as $entityKey => $entity) {
        $slug = $entityKey;

        Route::get($slug, [ModuleEntityController::class, 'index'])
            ->defaults('moduleKey', $moduleKey)
            ->defaults('entityKey', $entityKey)
            ->name("$entityKey.index");

        Route::get($slug . '/adauga', [ModuleEntityController::class, 'create'])
            ->defaults('moduleKey', $moduleKey)
            ->defaults('entityKey', $entityKey)
            ->name("$entityKey.create");

        Route::post($slug, [ModuleEntityController::class, 'store'])
            ->defaults('moduleKey', $moduleKey)
            ->defaults('entityKey', $entityKey)
            ->name("$entityKey.store");

        Route::get($slug . '/export-pdf', [ModuleEntityController::class, 'exportPdf'])
            ->defaults('moduleKey', $moduleKey)
            ->defaults('entityKey', $entityKey)
            ->name("$entityKey.export-pdf");

        if (!empty($entity['invoice_pdf'])) {
            Route::get($slug . '/{record}/pdf', [ModuleEntityController::class, 'exportInvoicePdf'])
                ->whereNumber('record')
                ->defaults('moduleKey', $moduleKey)
                ->defaults('entityKey', $entityKey)
                ->name("$entityKey.invoice-pdf");
        }

        Route::get($slug . '/{record}/modifica', [ModuleEntityController::class, 'edit'])
            ->whereNumber('record')
            ->defaults('moduleKey', $moduleKey)
            ->defaults('entityKey', $entityKey)
            ->name("$entityKey.edit");

        Route::put($slug . '/{record}', [ModuleEntityController::class, 'update'])
            ->whereNumber('record')
            ->defaults('moduleKey', $moduleKey)
            ->defaults('entityKey', $entityKey)
            ->name("$entityKey.update");

        Route::delete($slug . '/{record}', [ModuleEntityController::class, 'destroy'])
            ->whereNumber('record')
            ->defaults('moduleKey', $moduleKey)
            ->defaults('entityKey', $entityKey)
            ->name("$entityKey.destroy");
    }
});
