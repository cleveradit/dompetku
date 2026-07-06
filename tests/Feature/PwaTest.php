<?php

declare(strict_types=1);

// US-23: PWA installable + halaman offline.

it('serves a valid pwa manifest with the app name and both icon sizes', function () {
    expect(file_exists(public_path('manifest.webmanifest')))->toBeTrue();

    $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);

    expect($manifest)->not->toBeNull()
        ->and($manifest['name'])->toBe('Dompetku')
        ->and($manifest['display'])->toBe('standalone')
        ->and($manifest['icons'])->toHaveCount(4);
});

it('ships a minimal service worker and pwa icon files', function () {
    expect(file_exists(public_path('sw.js')))->toBeTrue()
        ->and(file_exists(public_path('icons/icon-192.png')))->toBeTrue()
        ->and(file_exists(public_path('icons/icon-512.png')))->toBeTrue();
});

// AC-23.2: halaman offline dapat diakses tanpa login dan menjelaskan kebutuhan koneksi.
it('renders the offline page for guests', function () {
    $this->get('/offline')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('offline', false));
});
