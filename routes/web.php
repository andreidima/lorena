<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\Process\Process;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Tech\CronJobLogController;
use App\Http\Controllers\Tech\ImpersonationController;
use App\Http\Controllers\Tech\MigrationController;
use App\Http\Controllers\Tech\PartnerStockController;


Auth::routes(['register' => false, 'password.request' => false, 'reset' => false]);

Route::redirect('/', '/acasa');

Route::middleware(['auth', 'checkUserActiv'])->group(function () {
    Route::get('/acasa', [HomeController::class, 'index'])->name('acasa');

    Route::resource('/utilizatori', UserController::class)->parameters(['utilizatori' => 'user'])->names('users')
        ->middleware('checkUserRole:Admin,SuperAdmin');

    require __DIR__ . '/modules/proiecte.php';
    require __DIR__ . '/modules/contracte.php';
    require __DIR__ . '/modules/aprovizionare.php';
    require __DIR__ . '/modules/mijloace-fixe.php';
    require __DIR__ . '/modules/contabilitate.php';
    require __DIR__ . '/modules/raportare.php';

    Route::prefix('tech')->name('tech.')->middleware('checkUserRole:SuperAdmin')->group(function () {
        Route::get('impersonare', [ImpersonationController::class, 'index'])->name('impersonation.index');
        Route::post('impersonare/{user}', [ImpersonationController::class, 'impersonate'])->name('impersonation.start');

        Route::get('cronjobs', [CronJobLogController::class, 'index'])->name('cronjobs.index');
        Route::get('stocuri-partener', [PartnerStockController::class, 'index'])->name('partner-stock.index');

        Route::get('migratii', [MigrationController::class, 'index'])->name('migrations.index');
        Route::post('migratii/ruleaza', [MigrationController::class, 'run'])->name('migrations.run');
        Route::post('migratii/{migration}/anuleaza', [MigrationController::class, 'undo'])->name('migrations.undo');
    });

    Route::post('impersonare/opreste', [ImpersonationController::class, 'stop'])
        ->name('tech.impersonation.stop');
});
