<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bot;

class BotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $bot = Bot::create([
            'name'                           => 'SeedBot',
            'trade_started_at'               => '2021-04-03 00:00:00',
            'initial_bet'                    => '100.50',
            'max_bet'                        => '1050.20',
            'stock_type'                     => 'BTC',
            'target_profit_percent'          => '500.0',
            'trailing_profit_percent'        => '25.0',
            'stop_loss_percent'              => '10.0',
            'user_id'                        => '1',
        ]);
    }
}
