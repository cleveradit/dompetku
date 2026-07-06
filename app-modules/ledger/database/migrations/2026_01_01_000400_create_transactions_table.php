<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->foreignId('destination_wallet_id')->nullable()->constrained('wallets')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('recurring_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->decimal('amount', 15, 2);
            $table->string('description', 255)->nullable();
            $table->date('occurred_on');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'occurred_on']);
            $table->index(['wallet_id', 'occurred_on']);
            $table->index('category_id');
            $table->index(['user_id', 'type', 'occurred_on']);
            $table->index(['recurring_transaction_id', 'occurred_on']);
        });

        DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_transactions_amount CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
