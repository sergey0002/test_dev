<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriberSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('subscribers')->upsert([
            ['id' => 1, 'email' => 'ivan@example.test', 'phone' => '+79990000001', 'name' => 'Иван', 'is_active' => true],
            ['id' => 2, 'email' => 'olga@example.test', 'phone' => '+79990000002', 'name' => 'Ольга', 'is_active' => true],
            ['id' => 3, 'email' => 'petr@example.test', 'phone' => '+79990000003', 'name' => 'Петр', 'is_active' => true],
            ['id' => 4, 'email' => 'temp@temporary-error.test', 'phone' => '+79990000004', 'name' => 'Email temporary', 'is_active' => true],
            ['id' => 5, 'email' => 'bad@invalid.test', 'phone' => '+79990000005', 'name' => 'Email invalid', 'is_active' => true],
            ['id' => 6, 'email' => 'sms-ok@example.test', 'phone' => '+79990000123', 'name' => 'SMS ok', 'is_active' => true],
            ['id' => 7, 'email' => 'sms-temp@example.test', 'phone' => '+79990000000', 'name' => 'SMS temporary', 'is_active' => true],
            ['id' => 8, 'email' => 'sms-bad@example.test', 'phone' => '+79990000999', 'name' => 'SMS invalid', 'is_active' => true],
            ['id' => 9, 'email' => null, 'phone' => '+79990000009', 'name' => 'Only sms', 'is_active' => true],
            ['id' => 10, 'email' => 'email-only@example.test', 'phone' => null, 'name' => 'Only email', 'is_active' => true],
        ], ['id'], ['email', 'phone', 'name', 'is_active']);
    }
}
