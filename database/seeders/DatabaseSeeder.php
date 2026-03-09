<?php

namespace Database\Seeders;

use App\Models\CronJobLog;
use App\Models\User;
use Database\Seeders\Modules\ModuleDataSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->create([
            'name' => 'Administrator General',
            'email' => 'admin@firma-utilaje.ro',
            'password' => Hash::make('parola1234'),
            'telefon' => '0711223344',
            'role' => 'SuperAdmin',
            'activ' => 1,
            'email_verified_at' => now(),
        ]);

        User::factory(59)->create()->each(function (User $user) {
            $user->update([
                'name' => fake('ro_RO')->name(),
                'telefon' => '07' . random_int(10000000, 99999999),
                'role' => fake()->randomElement(['Admin', 'Operator']),
                'activ' => 1,
                'password' => Hash::make('parola1234'),
            ]);
        });

        CronJobLog::query()->insert(
            collect(range(1, 60))->map(function (int $index) {
                return [
                    'job_name' => fake()->randomElement([
                        'sincronizare_stocuri',
                        'actualizare_curs_valutar',
                        'recalcul_profitabilitate',
                        'import_comenzi_furnizor',
                        'agregare_kpi_modul',
                    ]),
                    'ran_at' => now()->subMinutes($index * 5),
                    'status' => fake()->randomElement(['success', 'running', 'failed']),
                    'details' => 'Executie automata pentru fluxurile companiei.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all()
        );

        $this->call(ModuleDataSeeder::class);
    }
}
