<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('market_data', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->timestamp('date');
            $table->string('symbol');
            $table->decimal('open_price', 10, 4)->unsigned();
            $table->decimal('high_price', 10, 4)->unsigned();
            $table->decimal('low_price', 10, 4)->unsigned();
            $table->decimal('close_price', 10, 4)->unsigned();
            $table->decimal('crypto_currency_volume', 16, 8)->unsigned();
            $table->decimal('base_currency_volume', 16, 8)->unsigned();
            $table->integer('trade_count')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('market_data');
    }
}
