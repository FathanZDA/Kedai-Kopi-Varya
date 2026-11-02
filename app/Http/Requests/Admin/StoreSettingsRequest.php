<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form Informasi Toko (PRD AD-05, §8.9).
 *
 * Satu form mengirim profil toko, kontak, lokasi, fasilitas, dan jam
 * operasional tujuh hari sekaligus. Jam disimpan di tabel `store_hours`
 * (F-34); hari memakai 0 = Minggu sampai 6 = Sabtu.
 */
class StoreSettingsRequest extends FormRequest
{
    private const MAKS_FASILITAS = 20;

    private const MAKS_PANJANG_FASILITAS = 50;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_toko' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'sejarah' => ['nullable', 'string', 'max:5000'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'maps_url' => ['nullable', 'string', 'max:500', 'url'],
            'maps_embed' => ['nullable', 'string', 'max:1000', 'url'],
            'telepon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'instagram' => ['nullable', 'string', 'max:100'],
            'fasilitas' => ['nullable', 'string', 'max:1000'],
            'jam' => ['required', 'array'],
            'jam.*' => ['array'],
            'jam.*.buka' => ['nullable', 'date_format:H:i'],
            'jam.*.tutup' => ['nullable', 'date_format:H:i'],
            'jam.*.libur' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_toko.required' => 'Nama kedai wajib diisi.',
            'nama_toko.max' => 'Nama kedai maksimal 255 karakter.',
            'tagline.max' => 'Tagline maksimal 255 karakter.',
            'sejarah.max' => 'Deskripsi tentang kami maksimal 5000 karakter.',
            'alamat.max' => 'Alamat maksimal 500 karakter.',
            'maps_url.url' => 'Tautan Google Maps harus berupa URL yang valid.',
            'maps_embed.url' => 'URL embed peta harus berupa URL yang valid.',
            'telepon.max' => 'Nomor telepon maksimal 30 karakter.',
            'telepon.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
            'whatsapp.max' => 'Nomor WhatsApp maksimal 30 karakter.',
            'whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
            'instagram.max' => 'Akun Instagram maksimal 100 karakter.',
            'fasilitas.max' => 'Daftar fasilitas maksimal 1000 karakter.',
            'jam.required' => 'Jadwal operasional tujuh hari wajib dikirim.',
            'jam.*.buka.date_format' => 'Format jam buka harus HH:MM.',
            'jam.*.tutup.date_format' => 'Format jam tutup harus HH:MM.',
        ];
    }

    /**
     * Setiap hari harus punya jam buka/tutup, kecuali ditandai libur.
     * Daftar fasilitas juga dibatasi agar tidak menjadi teks bebas tak terbatas.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (range(0, 6) as $hari) {
                $libur = $this->boolean("jam.{$hari}.libur");
                $buka = $this->input("jam.{$hari}.buka");
                $tutup = $this->input("jam.{$hari}.tutup");

                if (! $libur && (! $buka || ! $tutup)) {
                    $validator->errors()->add(
                        "jam.{$hari}.buka",
                        'Jam buka dan jam tutup wajib diisi, atau tandai hari tersebut libur.',
                    );
                }
            }

            $fasilitas = $this->daftarFasilitas($this->input('fasilitas'));

            if (count($fasilitas) > self::MAKS_FASILITAS) {
                $validator->errors()->add(
                    'fasilitas',
                    'Daftar fasilitas maksimal '.self::MAKS_FASILITAS.' item.',
                );
            }

            foreach ($fasilitas as $item) {
                if (mb_strlen($item) > self::MAKS_PANJANG_FASILITAS) {
                    $validator->errors()->add(
                        'fasilitas',
                        'Setiap fasilitas maksimal '.self::MAKS_PANJANG_FASILITAS.' karakter.',
                    );

                    break;
                }
            }
        });
    }

    /**
     * Data siap simpan. Nama field mengikuti kolom `store_settings`, kecuali
     * `jam` yang diolah terpisah oleh service menjadi baris `store_hours`.
     *
     * @return array<string, mixed>
     */
    public function dataToko(): array
    {
        $data = $this->validated();

        return [
            'nama_toko' => (string) $data['nama_toko'],
            'tagline' => trim((string) $this->input('tagline')),
            'sejarah' => $this->isiAtauNull('sejarah'),
            'alamat' => $this->isiAtauNull('alamat'),
            'maps_url' => $this->isiAtauNull('maps_url'),
            'maps_embed' => $this->isiAtauNull('maps_embed'),
            'telepon' => $this->isiAtauNull('telepon'),
            'whatsapp' => $this->isiAtauNull('whatsapp'),
            'instagram' => $this->instagram(),
            'fasilitas' => $this->daftarFasilitas($data['fasilitas'] ?? null),
            'jam' => $this->jam(),
        ];
    }

    /**
     * @return array<int, array{buka: ?string, tutup: ?string, libur: bool}>
     */
    private function jam(): array
    {
        $jam = [];

        foreach (range(0, 6) as $hari) {
            $libur = $this->boolean("jam.{$hari}.libur");

            $jam[$hari] = [
                'buka' => $libur ? null : $this->input("jam.{$hari}.buka"),
                'tutup' => $libur ? null : $this->input("jam.{$hari}.tutup"),
                'libur' => $libur,
            ];
        }

        return $jam;
    }

    private function instagram(): ?string
    {
        $handle = ltrim(trim((string) $this->input('instagram')), '@');

        return $handle === '' ? null : '@'.$handle;
    }

    /**
     * @return list<string>
     */
    private function daftarFasilitas(?string $teks): array
    {
        return collect(explode(',', (string) $teks))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function isiAtauNull(string $kunci): ?string
    {
        $nilai = trim((string) $this->input($kunci));

        return $nilai === '' ? null : $nilai;
    }
}
