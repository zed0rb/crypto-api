<?php

use App\Currency;
use Illuminate\Database\Seeder;

class CurrenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Currency::count()) {
            return;
        }

        $currenciesToCreate = ['BTC', 'ETH', 'IOTA'];

        foreach ($currenciesToCreate as $currency) {
            Currency::create([
                'ticker' => $currency
            ]);
        }
    }
}
