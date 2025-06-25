
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSlotBetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('slot_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('bet_amount', 18, 8);
            $table->string('client_seed');
            $table->string('server_seed');
            $table->string('server_seed_hash');
            $table->string('result_symbols');
            $table->decimal('multiplier', 8, 2)->default(0);
            $table->decimal('payout', 18, 8)->default(0);
            $table->string('currency');
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
        Schema::dropIfExists('slot_bets');
    }
}
