<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->bigInteger('user_id')->unsigned();
            $table->string('name');
            $table->timestamp('trade_started_at');
            $table->timestamp('trade_ended_at')->nullable();
            $table->decimal('initial_bet', 8, 2)->unsigned();
            $table->decimal('max_bet', 8, 2)->unsigned();
            $table->decimal('buy_price', 10, 4)->unsigned()->nullable();
            $table->string('stock_type', 5);
            $table->decimal('current_high_price', 10, 4)->unsigned()->nullable();
            $table->decimal('current_low_price', 10, 4)->unsigned()->nullable();
            $table->decimal('target_profit_percent', 6, 3)->unsigned();
            $table->decimal('trailing_profit_percent', 6, 3)->unsigned();
            $table->decimal('stop_loss_percent', 6, 3)->unsigned();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bots');
    }
}
