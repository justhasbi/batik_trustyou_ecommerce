<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    protected array $shippingLabels = [
        'not_shipped' => 'belum dikirim',
        'packed'      => 'sedang dikemas',
        'shipped'     => 'sudah dikirim',
        'in_transit'  => 'dalam perjalanan',
        'delivered'   => 'sudah diterima',
    ];

    // Menu pra-chat: pelanggan memilih chat dengan bot atau admin
    public function start(Request $request)
    {
        $data = $request->validate(['mode' => ['required', 'in:bot,admin']]);
        $user = auth('sanctum')->user();

        $session = ChatSession::create([
            'user_id'    => $user?->id,
            'session_id' => $request->header('X-Cart-Token') ?? (string) Str::uuid(),
            'mode'       => $data['mode'],
            'status'     => 'active',
        ]);

        $greeting = $data['mode'] === 'admin'
            ? 'Anda terhubung dengan admin. Silakan tulis pertanyaan Anda, admin akan segera membalas pada jam operasional (08.00-20.00 WIB).'
            : 'Halo! Saya asisten Batik TrustYou. Ada yang bisa dibantu seputar produk, ukuran, pengiriman, atau retur?';

        $session->messages()->create([
            'sender'  => $data['mode'] === 'admin' ? 'admin' : 'bot',
            'message' => $greeting,
        ]);

        return response()->json([
            'session_id' => $session->id,
            'mode'       => $session->mode,
            'reply'      => $greeting,
        ]);
    }

    public function message(Request $request)
    {
        $data = $request->validate([
            'session_id' => ['required', 'exists:chat_sessions,id'],
            'message'    => ['required', 'string', 'max:1000'],
            'tinggi'     => ['nullable', 'numeric'],
            'berat'      => ['nullable', 'numeric'],
        ]);

        $session = ChatSession::findOrFail($data['session_id']);

        /** @var \App\Models\User|null $user */
        $user = auth('sanctum')->user();

        $session->messages()->create(['sender' => 'customer', 'message' => $data['message']]);

        // Mode admin: pesan disimpan, admin membalas dari dashboard
        if ($session->mode === 'admin') {
            return response()->json(['reply' => null, 'pending_admin' => true]);
        }

        // Mode bot: teruskan ke service Python untuk klasifikasi intent
        try {
            $res = Http::timeout(5)
                ->post(config('services.chatbot.url') . '/predict', [
                    'message'          => $data['message'],
                    'is_authenticated' => $user !== null,
                    'tinggi'           => $data['tinggi'] ?? null,
                    'berat'            => $data['berat'] ?? null,
                ])
                ->json();
        } catch (\Throwable $e) {
            return response()->json([
                'reply'         => 'Maaf, asisten sedang tidak tersedia. Mau saya hubungkan ke admin?',
                'suggest_admin' => true,
            ]);
        }

        $intent = $res['intent'] ?? 'fallback';
        $reply  = $res['reply'] ?? null;

        // Status pengiriman: Laravel yang mengambil data order (Python tak menyentuh DB)
        if ($intent === 'status_pengiriman' && $user) {
            $order = $user->orders()->latest()->first();
            if ($order) {
                $label = $this->shippingLabels[$order->shipping_status] ?? $order->shipping_status;
                $reply = "Pesanan {$order->order_number} saat ini {$label}.";
                if ($order->tracking_number) {
                    $reply .= " No. resi: {$order->tracking_number}"
                        . ($order->courier ? " ({$order->courier})" : '') . '.';
                }
            } else {
                $reply = 'Belum ada pesanan yang bisa dilacak pada akun Anda.';
            }
        }

        $reply = $reply ?? 'Maaf, saya belum paham. Mau saya hubungkan ke admin?';

        $session->messages()->create([
            'sender'  => 'bot',
            'message' => $reply,
            'intent'  => $intent,
        ]);

        return response()->json([
            'reply'         => $reply,
            'intent'        => $intent,
            'suggest_admin' => $res['suggest_admin'] ?? false,
            'require_login' => $res['require_login'] ?? false,
        ]);
    }

    // Alihkan sesi dari bot ke admin (dipanggil saat bot menawarkan admin)
    public function switchToAdmin(Request $request)
    {
        $data = $request->validate(['session_id' => ['required', 'exists:chat_sessions,id']]);
        $session = ChatSession::findOrFail($data['session_id']);
        $session->update(['mode' => 'admin']);

        $reply = 'Baik, Anda saya alihkan ke admin. Mohon tunggu ya, admin akan membalas segera.';
        $session->messages()->create(['sender' => 'admin', 'message' => $reply]);

        return response()->json(['mode' => 'admin', 'reply' => $reply]);
    }

    // Ambil pesan baru dalam sesi (dipakai widget untuk polling balasan admin).
    // Kirim ?after={id_terakhir} agar hanya pesan baru yang dikembalikan.
    public function messages(Request $request, ChatSession $session)
    {
        $after = (int) $request->query('after', 0);

        $messages = $session->messages()
            ->where('id', '>', $after)
            ->orderBy('id')
            ->get(['id', 'sender', 'message', 'created_at']);

        return response()->json(['messages' => $messages]);
    }
}