<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reference data needed in every environment, like currencies in config/fund.php.
        $this->call(FinancialFrameworkSeeder::class);

        // Demo data is for local and staging work only; production starts empty.
        if (app()->environment('production')) {
            $this->command?->warn('Skipping demo data: this is production.');

            return;
        }

        $this->call(DemoFundSeeder::class);
    }
}
