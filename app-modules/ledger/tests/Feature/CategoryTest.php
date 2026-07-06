<?php

declare(strict_types=1);

use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Wallet\Infrastructure\Models\Wallet;

// AC-09.1
it('creates a category unique per type', function () {
    $user = User::factory()->create();
    Wallet::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->post('/categories', [
            'name' => 'Kopi',
            'type' => 'expense',
            'color' => '#B94A48',
            'icon' => 'coffee',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    // Nama sama pada tipe sama ditolak…
    $this->actingAs($user)
        ->post('/categories', ['name' => 'Kopi', 'type' => 'expense'])
        ->assertSessionHasErrors('name');

    // …tetapi boleh pada tipe berbeda.
    $this->actingAs($user)
        ->post('/categories', ['name' => 'Kopi', 'type' => 'income'])
        ->assertSessionHasNoErrors();
});

// AC-09.2
it('soft deletes an unused category', function () {
    $user = User::factory()->create();
    Wallet::factory()->forUser($user)->create();
    $category = Category::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->delete("/categories/{$category->id}")
        ->assertRedirect();

    $this->assertSoftDeleted('categories', ['id' => $category->id]);
});

// AC-09.3
it('refuses to delete a category in use and mentions the usage count', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->create();
    $category = Category::factory()->forUser($user)->expense()->create();
    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($category)->count(3)->create();

    $this->actingAs($user)
        ->delete("/categories/{$category->id}")
        ->assertRedirect();

    expect(session('error'))->toContain('3');
    $this->assertNotSoftDeleted('categories', ['id' => $category->id]);
});

// AC-09.4
it('locks the category type once used by transactions', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->create();
    $category = Category::factory()->forUser($user)->expense()->create(['name' => 'Jajan']);
    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($category)->create();

    // Ganti tipe ditolak.
    $this->actingAs($user)
        ->patch("/categories/{$category->id}", ['name' => 'Jajan', 'type' => 'income'])
        ->assertRedirect();

    expect(session('error'))->toContain('Tipe kategori')
        ->and($category->refresh()->type->value)->toBe('expense');

    // Nama/warna/ikon tetap boleh.
    $this->actingAs($user)
        ->patch("/categories/{$category->id}", [
            'name' => 'Jajan Sore',
            'type' => 'expense',
            'color' => '#6D5BA8',
            'icon' => 'coffee',
        ])
        ->assertSessionHasNoErrors();

    expect($category->refresh()->name)->toBe('Jajan Sore');
});

// 04-NFR.md §1: isolasi data.
it('blocks another user from touching a category', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    Wallet::factory()->forUser($intruder)->create();
    $category = Category::factory()->forUser($owner)->create();

    $this->actingAs($intruder)
        ->patch("/categories/{$category->id}", ['name' => 'Curian', 'type' => 'expense'])
        ->assertNotFound();

    $this->actingAs($intruder)
        ->delete("/categories/{$category->id}")
        ->assertNotFound();
});
