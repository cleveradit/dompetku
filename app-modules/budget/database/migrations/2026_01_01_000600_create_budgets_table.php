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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->date('month');
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['user_id', 'category_id', 'month']);
            $table->index(['user_id', 'month']);
        });

        DB::statement('ALTER TABLE budgets ADD CONSTRAINT chk_budgets_amount CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
