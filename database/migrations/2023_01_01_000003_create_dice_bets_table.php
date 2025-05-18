<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiceBetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dice_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('bet_amount', 16, 8);
            $table->string('currency'); // BTC, ETH
            $table->string('client_seed');
            $table->string('server_seed');
            $table->string('server_seed_hash');
            $table->decimal('roll_result', 5, 2); // 0.00 - 99.99
            $table->string('prediction'); // 'over' or 'under'
            $table->decimal('predicted_number', 5, 2); // 0.00 - 99.99
            $table->decimal('multiplier', 10, 4);
            $table->boolean('won')->default(false);
            $table->decimal('payout', 16, 8)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dice_bets');
    }
}
