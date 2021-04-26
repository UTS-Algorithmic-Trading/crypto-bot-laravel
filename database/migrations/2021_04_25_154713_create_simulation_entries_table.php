<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSimulationEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('simulation_entries', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->bigInteger('simulation_id')->unsigned();
            $table->bigInteger('bot_id')->unsigned();
            $table->foreign('simulation_id')->references('id')->on('simulations');
            $table->foreign('bot_id')->references('id')->on('bots');
            $table->decimal('amount', 16, 8);
            $table->decimal('buy_price', 16, 8)->unsigned();
            $table->decimal('sell_price', 16, 8)->unsigned();
            $table->string('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('simulation_entries');
    }
}
