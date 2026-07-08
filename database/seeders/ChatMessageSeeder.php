<?php

namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Database\Seeder;

class ChatMessageSeeder extends Seeder
{
    public function run(): void
    {
        $botSession = ChatSession::where('session_id', 'demo-session-bot')->first();
        $adminSession = ChatSession::where('session_id', 'demo-session-admin')->first();

        if ($botSession) {
            $messages = [
                ['sender' => 'bot',      'message' => 'Halo! Saya asisten Batik TrustYou. Ada yang bisa dibantu?', 'intent' => null],
                ['sender' => 'customer', 'message' => 'saya tinggi 172 berat 70, ukuran apa ya?',                 'intent' => null],
                ['sender' => 'bot',      'message' => 'Untuk tinggi 172 cm dan berat 70 kg, ukuran yang direkomendasikan adalah L.', 'intent' => 'rekomendasi_ukuran'],
                ['sender' => 'customer', 'message' => 'pesanan saya sudah dikirim belum?',                        'intent' => null],
                ['sender' => 'bot',      'message' => 'Pesanan INV-DEMO0001 saat ini sudah dikirim. No. resi: JNE1234567890 (JNE).', 'intent' => 'status_pengiriman'],
            ];
            foreach ($messages as $i => $m) {
                ChatMessage::updateOrCreate(
                    ['chat_session_id' => $botSession->id, 'message' => $m['message']],
                    array_merge($m, ['chat_session_id' => $botSession->id])
                );
            }
        }

        if ($adminSession) {
            $messages = [
                ['sender' => 'admin',    'message' => 'Anda terhubung dengan admin. Ada yang bisa kami bantu?', 'intent' => null],
                ['sender' => 'customer', 'message' => 'apakah bisa custom motif untuk seragam kantor?',          'intent' => null],
            ];
            foreach ($messages as $m) {
                ChatMessage::updateOrCreate(
                    ['chat_session_id' => $adminSession->id, 'message' => $m['message']],
                    array_merge($m, ['chat_session_id' => $adminSession->id])
                );
            }
        }
    }
}