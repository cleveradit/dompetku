<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// 01-ARCHITECTURE.md konvensi #6: recurring dijalankan scheduler harian,
// idempotent — aman dijalankan dua kali.
Schedule::command('recurring:run')->dailyAt('00:05')->timezone('Asia/Jakarta');
