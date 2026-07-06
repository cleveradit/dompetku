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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->enum('type', ['cash', 'bank', 'ewallet', 'other'])->default('other');
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->string('color', 7)->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_archived')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'is_archived']);
        });

        DB::statement('ALTER TABLE wallets ADD CONSTRAINT chk_wallets_initial_balance CHECK (initial_balance >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
