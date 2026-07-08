<?php

namespace Database\Seeders;

use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChatSessionSeeder extends Seeder
{
    public function run(): void
    {
        $budi = User::where('email', 'budi@example.com')->first();

        // Sesi bot untuk pelanggan login
        ChatSession::updateOrCreate(
            ['session_id' => 'demo-session-bot'],
            ['user_id' => $budi?->id, 'mode' => 'bot', 'status' => 'active']
        );

        // Sesi admin untuk tamu (belum login)
        ChatSession::updateOrCreate(
            ['session_id' => 'demo-session-admin'],
            ['user_id' => null, 'mode' => 'admin', 'status' => 'active']
        );
    }
}