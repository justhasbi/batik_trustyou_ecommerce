<?php

namespace App\Support;

/**
 * Sumber kebenaran (single source of truth) untuk opsi pembayaran & pengiriman.
 *
 * Harga ongkir dan daftar metode bayar ditentukan di sini (server-side) supaya
 * tidak bisa dimanipulasi dari frontend. Frontend hanya mengirim ID pilihan,
 * lalu backend yang menghitung biaya & membuat instruksi pembayaran.
 *
 * Catatan: seluruh pembayaran di sini bersifat DUMMY (untuk keperluan demo/TA).
 */
class CheckoutOptions
{
    /**
     * Metode pembayaran yang tersedia.
     * type: qr | va | cod  -> menentukan cara menampilkan instruksi bayar di frontend.
     */
    public const PAYMENT_METHODS = [
        [
            'id'       => 'qris',
            'name'     => 'QRIS',
            'type'     => 'qr',
            'desc'     => 'Bayar dengan scan QR dari aplikasi bank / e-wallet apa pun.',
            'channels' => [],
        ],
        [
            'id'       => 'ewallet',
            'name'     => 'E-Wallet',
            'type'     => 'qr',
            'desc'     => 'GoPay, OVO, DANA, atau ShopeePay.',
            'channels' => ['GoPay', 'OVO', 'DANA', 'ShopeePay'],
        ],
        [
            'id'       => 'bank_transfer',
            'name'     => 'Transfer Bank (Virtual Account)',
            'type'     => 'va',
            'desc'     => 'Transfer ke nomor Virtual Account yang kami buatkan.',
            'channels' => ['BCA', 'BNI', 'BRI', 'Mandiri'],
        ],
        [
            'id'       => 'cod',
            'name'     => 'Bayar di Tempat (COD)',
            'type'     => 'cod',
            'desc'     => 'Bayar tunai saat barang diterima.',
            'channels' => [],
        ],
    ];

    /**
     * Opsi pengiriman beserta ongkos kirim (dummy).
     */
    public const SHIPPING_OPTIONS = [
        ['id' => 'jne_reg',      'courier' => 'JNE',      'service' => 'Reguler',      'cost' => 15000, 'etd' => '2-3 hari'],
        ['id' => 'jne_yes',      'courier' => 'JNE',      'service' => 'YES (Express)', 'cost' => 32000, 'etd' => '1 hari'],
        ['id' => 'jnt_reg',      'courier' => 'J&T',      'service' => 'Reguler',      'cost' => 14000, 'etd' => '2-4 hari'],
        ['id' => 'sicepat_reg',  'courier' => 'SiCepat',  'service' => 'REG',          'cost' => 13000, 'etd' => '2-3 hari'],
        ['id' => 'anteraja_next','courier' => 'AnterAja', 'service' => 'Next Day',     'cost' => 28000, 'etd' => '1 hari'],
    ];

    public static function paymentMethod(string $id): ?array
    {
        foreach (self::PAYMENT_METHODS as $method) {
            if ($method['id'] === $id) {
                return $method;
            }
        }
        return null;
    }

    public static function shippingOption(string $id): ?array
    {
        foreach (self::SHIPPING_OPTIONS as $option) {
            if ($option['id'] === $id) {
                return $option;
            }
        }
        return null;
    }

    public static function paymentMethodIds(): array
    {
        return array_column(self::PAYMENT_METHODS, 'id');
    }

    public static function shippingOptionIds(): array
    {
        return array_column(self::SHIPPING_OPTIONS, 'id');
    }
}
