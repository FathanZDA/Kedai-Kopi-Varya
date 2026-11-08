<?php

namespace App\Http\Controllers;

use App\Services\MidtransNotificationService;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint publik notifikasi pembayaran Midtrans.
 *
 * Tidak memakai auth dan dikecualikan dari CSRF karena Midtrans tidak
 * mengirim token (docs/ROUTES.md §4.7). Signature diverifikasi lebih dulu;
 * hanya notifikasi yang sah yang boleh mengubah status pembayaran.
 */
class MidtransNotificationController extends Controller
{
    public function __construct(
        private readonly MidtransNotificationService $notifikasi,
        private readonly MidtransService $midtrans,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! $this->notifikasi->payloadLengkap($payload)) {
            Log::warning('Notifikasi Midtrans tidak lengkap dan diabaikan.', [
                'payload' => $payload,
            ]);

            // Payload rusak tidak akan membaik bila diulang, jadi tetap 200.
            return response()->json(['message' => 'Payload tidak lengkap.'], 200);
        }

        if (! $this->midtrans->terkonfigurasi()) {
            Log::error('Notifikasi Midtrans tidak dapat diverifikasi: MIDTRANS_SERVER_KEY kosong.');

            // Kegagalan konfigurasi bersifat sementara dari sisi Midtrans:
            // kembalikan 500 agar notifikasi dicoba ulang setelah .env diisi.
            return response()->json(['message' => 'Kredensial Midtrans belum diatur.'], 500);
        }

        if (! $this->notifikasi->signatureValid($payload)) {
            // Payload dengan signature salah tidak disimpan agar request palsu
            // tidak bisa membanjiri tabel audit (PRD §11.4.4 cukup log).
            Log::warning('Notifikasi Midtrans dengan signature tidak valid.', [
                'midtrans_order_id' => $payload['order_id'],
                'transaction_status' => $payload['transaction_status'],
            ]);

            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        $notifikasi = $this->notifikasi->proses($payload);

        return response()->json([
            'message' => 'Notifikasi diproses.',
            'status_pemrosesan' => $notifikasi->status_pemrosesan->value,
        ]);
    }
}
