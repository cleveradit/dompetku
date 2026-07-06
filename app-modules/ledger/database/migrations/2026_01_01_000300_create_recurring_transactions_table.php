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
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_wallet_id')->nullable()->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->decimal('amount', 15, 2);
            $table->string('description', 255)->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->unsignedSmallInteger('interval')->default(1);
            $table->date('next_run_on');
            $table->date('end_on')->nullable();
            $table->date('last_run_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'next_run_on']);
            $table->index('user_id');
        });

        DB::statement('ALTER TABLE recurring_transactions ADD CONSTRAINT chk_recurring_amount CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
