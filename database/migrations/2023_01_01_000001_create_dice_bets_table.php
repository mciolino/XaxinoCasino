<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dice_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('bet_amount', 18, 8);
            $table->string('client_seed');
            $table->string('server_seed');
            $table->string('server_seed_hash');
            $table->decimal('rolled_number', 5, 2); // 0.00-99.99
            $table->decimal('payout', 18, 8);
            $table->string('currency'); // BTC, ETH, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dice_bets');
    }
};
