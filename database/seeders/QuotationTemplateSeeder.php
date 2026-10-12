<?php

namespace Database\Seeders;

use App\Models\QuotationTemplate;
use Illuminate\Database\Seeder;

class QuotationTemplateSeeder extends Seeder
{
    /**
     * Seed default quotation templates (one per project type). Idempotent.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Sistem ERP / Software',
                'project_type' => 'Aplikasi Web Internal — Phase 1 (MVP)',
                'default_title' => 'Sistem ERP — Procure-to-Pay & Order-to-Cash',
                'default_summary' => 'Dokumen ini adalah penawaran resmi pengembangan sistem — aplikasi web internal yang mengelola seluruh alur bisnis dari permintaan pelanggan hingga pembayaran diterima dalam satu sistem yang saling terhubung.',
                'default_scope' => null,
                'default_terms' => 'Harga belum termasuk PPN 11%. Setiap milestone mendapat 2 putaran revisi mayor gratis.',
                'default_exclusions' => 'Integrasi bank otomatis, mobile app native, domain & hosting bulanan, migrasi data dari sistem lama.',
                'default_notes' => 'Harga bersifat all-in untuk seluruh fitur Phase 1.',
                'default_validity_days' => 3,
                'default_tax_rate' => 11,
            ],
            [
                'name' => 'Pengadaan Barang',
                'project_type' => 'Pengadaan Barang',
                'default_title' => 'Penawaran Pengadaan Barang',
                'default_summary' => 'Penawaran resmi pengadaan barang sesuai spesifikasi terlampir, termasuk pengiriman dan garansi.',
                'default_scope' => null,
                'default_terms' => 'Harga belum termasuk PPN 11%. Pembayaran sesuai termin yang disepakati.',
                'default_exclusions' => 'Biaya instalasi di luar lokasi yang disepakati, perubahan spesifikasi setelah PO.',
                'default_notes' => null,
                'default_validity_days' => 7,
                'default_tax_rate' => 11,
            ],
            [
                'name' => 'Jasa Umum',
                'project_type' => 'Jasa',
                'default_title' => 'Penawaran Jasa',
                'default_summary' => 'Penawaran resmi pekerjaan jasa sesuai ruang lingkup terlampir.',
                'default_scope' => null,
                'default_terms' => 'Harga belum termasuk PPN 11%.',
                'default_exclusions' => 'Pekerjaan di luar ruang lingkup yang disepakati.',
                'default_notes' => null,
                'default_validity_days' => 7,
                'default_tax_rate' => 11,
            ],
        ];

        foreach ($templates as $data) {
            QuotationTemplate::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
