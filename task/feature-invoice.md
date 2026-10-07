<!-- Gabungan dari 10 file breakdown task invoice. Acuan kerja AI agent. Eksekusi berurutan Bagian 00 sampai 08. -->

---

# Task Breakdown — Fitur Invoice

Acuan kerja AI agent. Eksekusi berurutan dari Bagian `00` → `08`.
Setiap bagian bersifat mandiri: baca Bagian `00 Kontrak` dulu sebelum mengerjakan bagian lain.

## Daftar Task

| # | Bagian | Tujuan | Ketergantungan |
|---|------|--------|----------------|
| 00 | Kontrak | Kontrak data, enum, penomoran, konvensi — WAJIB dibaca semua agent | - |
| 01 | Fondasi Data | Migrasi + Model + Factory + Calculator + NumberGenerator + test unit | 00 |
| 02 | Client & Invoice Resource | ClientResource + InvoiceResource (Generate + Manage) | 01 |
| 03 | Payment & Pay Slip | Payment + DP/cicil + Print Pay Slip | 01, 02 |
| 04 | PDF & Print | Export PDF DomPDF + Print browser | 01, 02 |
| 05 | Email | Kirim invoice via email + lampiran PDF (queue) | 01, 02, 04 |
| 06 | Settings & Widgets | Settings Hub sederhana + Widgets + scheduler overdue | 01, 02 |
| 07 | Shield & Security | Filament Shield + Role + Policy | 02, 03, 06 |
| 08 | QA & Release | QA, Pint, PHPStan, full test, seeder demo | 01–07 |

## Aturan Global (berlaku untuk semua task)

1. Baca Bagian 00 Kontrak sebelum mulai. Jangan mengubah kontrak tanpa persetujuan user.
2. Aktifkan skill yang relevan via tool `skill`:
   - Semua task Filament → `filament-development`
   - Semua kode Laravel/PHP → `laravel-best-practices`
   - Setiap menulis test → `testing-best-practices`
3. Gunakan `search-docs` sebelum memakai API Filament/Laravel yang belum pasti (packages: `filament/filament`, `laravel/framework`).
4. Buat file via `php artisan make:* --no-interaction` (jangan tulis manual kalau ada generatornya). Cek opsi dengan `--help` dulu.
5. PHP 8.4, Laravel 13, Filament 5.9. Curly braces wajib, constructor promotion, return type eksplisit, Enum key TitleCase.
6. DILARANG ubah `composer.json` / install package tanpa approval user (kecuali task yang menugaskannya eksplisit).
7. Setiap perubahan PHP → jalankan `vendor/bin/pint --dirty --format agent` di akhir task.
8. Setiap perubahan behavior/logic → tulis/ubah Pest test, jalankan sempit: `php artisan test --compact --filter=NamaTest`. Copy/layout saja tidak perlu test.
9. Jangan buat verification script / tinker untuk hal yang bisa dicover test. Jangan hapus test tanpa approval.
10. Definisi DONE tiap task ada di file masing-masing (Acceptance Criteria + perintah verifikasi harus hijau).

---

# 00 — Kontrak (Wajib Dibaca Semua Agent)

## Stack Terkunci

- PHP 8.4, Laravel 13.34, Filament 5.9, Livewire 4.4, SQLite (dev), Pest 5.3.
- Panel Filament: `id=admin`, `path=admin` (`app/Providers/Filament/AdminPanelProvider.php`).
- Auth ganda: Filament `->login()` vs WorkOS di `routes/web.php`. Model auth: `App\Models\User` (+ `HasRoles` setelah Shield terpasang).

## Enum

```php
App\Enums\InvoiceStatus: Draft, Sent, PartiallyPaid, Paid, Overdue, Cancelled
// value: draft, sent, partially_paid, paid, overdue, cancelled

App\Enums\DiscountType: Fixed, Percent
// value: fixed, percent

App\Enums\PaymentMethod: Transfer, Cash, Va, Qris
// value: transfer, cash, va, qris
```

## Skema (nama kolom dikunci)

**clients**: `id, name, company nullable, email, phone nullable, address nullable, tax_id nullable, notes nullable, is_active bool default true, timestamps, softDeletes`

**invoices**: `id, invoice_number unique, client_id fk cascade?->restrict (jangan cascade delete client), issue_date date, due_date date, status string default draft, subtotal decimal(15,2), discount_type nullable, discount_value decimal(15,2) default 0, tax_rate nullable decimal(5,2), tax_amount decimal(15,2) default 0, grand_total decimal(15,2), amount_paid decimal(15,2) default 0, amount_due decimal(15,2), currency default IDR, notes/terms/footer nullable text, sent_at nullable, paid_at nullable, created_by fk users nullable, timestamps, softDeletes`

**invoice_items**: `id, invoice_id fk cascade, description text, qty decimal(12,2), unit_price decimal(15,2), line_total decimal(15,2), sort_order int default 0, timestamps`

**payments**: `id, payment_number unique, invoice_id fk cascade, payment_date date, amount decimal(15,2), method string, reference nullable, notes nullable, received_by fk users nullable, timestamps`

**invoice_settings** (single row `id=1`): `company_name, company_address nullable, company_phone nullable, company_email nullable, logo_path nullable, invoice_prefix default INV, payment_prefix default PAY, yearly_reset bool default true, default_due_days int default 14, default_tax_rate nullable decimal(5,2), default_terms nullable text, default_notes nullable text, email_subject_template, email_body_template nullable text, allow_overpayment bool default false`

**number_counters** (untuk lock penomoran aman-concurrent): `id, type string (invoice|payment), year int, last_number int default 0, unique(type,year)`

## Aturan Kalkulasi (dikunci)

1. `line_total = qty * unit_price` (per baris, tanpa pajak per-baris — pajak hanya level invoice).
2. `subtotal = sum(line_total)`.
3. Diskon: `fixed → min(discount_value, subtotal)`; `percent → subtotal * discount_value/100`. Null = 0.
4. Pajak opsional: `taxable = subtotal - discount`; `tax_amount = tax_rate == null ? 0 : taxable * tax_rate/100`.
5. `grand_total = taxable + tax_amount`.
6. `amount_paid = sum(payments.amount)`; `amount_due = grand_total - amount_paid`.
7. Status otomatis setelah payment: `amount_paid <= 0 → Sent; 0 < paid < total → PartiallyPaid; paid >= total → Paid (+paid_at=now)`. Overdue hanya via scheduler (bukan dari payment). `Draft` tidak boleh menerima payment.

## Penomoran (dikunci)

- Format: `{PREFIX}-{YYYY}-{NNNN}` → `INV-2026-0001`, `PAY-2026-0001`. NNNN 4-digit zero-padded, reset tiap 1 Januari (query `number_counters` + `lockForUpdate` dalam transaction).
- Prefix & reset diambil dari `invoice_settings`. Jangan pakai `max()+1` tanpa lock.

## File Target (jangan buat struktur lain)

- Model: `app/Models/Client.php, Invoice.php, InvoiceItem.php, Payment.php, InvoiceSetting.php, NumberCounter.php`
- Enum: `app/Enums/InvoiceStatus.php, DiscountType.php, PaymentMethod.php`
- Service: `app/Services/InvoiceCalculator.php, InvoiceNumberGenerator.php, InvoicePdfService.php`
- Mail/Job: `app/Mail/InvoiceMail.php`, `app/Jobs/SendInvoiceEmailJob.php`
- Filament: `app/Filament/Resources/Clients/*`, `app/Filament/Resources/Invoices/*`, `app/Filament/Resources/Payments/*`, `app/Filament/Pages/SettingsHub.php + InvoiceSettingsPage.php`, `app/Filament/Widgets/InvoiceStatsOverview.php, RevenueChart.php, OverdueInvoicesTable.php`
- Blade: `resources/views/invoices/pdf.blade.php, print.blade.php`, `resources/views/payments/slip.blade.php`, `resources/views/emails/invoices/send.blade.php`
- Routes: tambah di `routes/web.php` (grup `auth`): `invoices/{invoice}/print`, `invoices/{invoice}/pdf`, `payments/{payment}/slip`
- Command: `app/Console/Commands/MarkOverdueInvoices.php` (`invoices:mark-overdue`)
- Test: `tests/Feature/Invoice/*Test.php`, `tests/Unit/Invoice/*Test.php`

---

# 01 — Fondasi Data (Migrasi + Model + Logic Inti)

## Objective

Bangun tabel, model, factory, enum, dan logic kalkulasi/penomoran sesuai `00-contracts.md`. Tanpa UI.

## Prerequisites

- Baca `00-contracts.md` + aktifkan skill `laravel-best-practices`.
- Cek versi: `composer show --direct`, `php artisan list | grep make`.

## Steps (agent-eksekusi)

1. Buat enum: `php artisan make:enum InvoiceStatus`, `DiscountType`, `PaymentMethod` (atau `make:class` jika `make:enum` tidak ada). Isi sesuai kontrak (value + TitleCase key).
2. Buat migrasi (urutan): `clients` → `invoices` → `invoice_items` → `payments` → `invoice_settings` → `number_counters`.
   - Perintah: `php artisan make:migration create_clients_table --no-interaction` (dst).
   - FK: `invoices.client_id → clients.id` (restrict on delete), `invoice_items/payments → invoices.id` (cascade on delete).
   - Decimal sesuai kontrak. `invoice_number/payment_number` unique.
3. Buat model + factory + seeder: `php artisan make:model Client --factory --no-interaction` (ulangi: Invoice, InvoiceItem, Payment, InvoiceSetting, NumberCounter).
   - Relasi: `Client hasMany Invoice`; `Invoice belongsTo Client, hasMany items/payments`; `Payment belongsTo Invoice`.
   - Casts: `issue_date/payment_date/due_date → date`, `status → InvoiceStatus`, `method → PaymentMethod`, decimal → `decimal:2`.
   - `InvoiceSetting`: method static `cached(): self` (Cache::rememberForever + `saved` bust cache).
4. Buat service:
   - `php artisan make:class Services/InvoiceCalculator --no-interaction` — method `calculate(subtotal, discountType, discountValue, taxRate): array{subtotal,discountAmount,taxAmount,grandTotal}` + `recalculate(Invoice): void` (update subtotal/tax/grand/paid/due + simpan).
   - `php artisan make:class Services/InvoiceNumberGenerator --no-interaction` — method `next(string $type, ?int $year): string` dengan `DB::transaction + NumberCounter::lockForUpdate()->firstOrCreate()`.
5. Observer/event: `InvoiceItem saved/deleted → recalculate(parent)`; `Payment saved/deleted → recalculate + transisi status` (hormati kontrak status; jangan override `Cancelled`).
6. Jalankan `php artisan migrate --no-interaction`.

## Files

- `database/migrations/*_create_*_table.php`, `app/Models/*.php`, `app/Enums/*.php`, `app/Services/*.php`, `database/factories/*.php`.

## Acceptance Criteria

- [ ] `php artisan migrate --no-interaction` hijau di SQLite fresh.
- [ ] Kalkulasi: diskon fixed/percent benar; `tax_rate=null → tax=0`; DP (`paid<total`) tidak menandai paid.
- [ ] Penomoran: dua request berurutan tidak duplikat; tahun baru reset ke `0001`.
- [ ] DILARANG ada UI Filament di task ini.

## Tests (wajib, Pest)

- Aktifkan skill `testing-best-practices`. Buat: `php artisan make:test --pest Invoice/CalculatorTest`, `Invoice/NumberGeneratorTest`.
- Cover: subtotal/diskon/pajak-null/pajak-isi, overpayment cache, sequence yearly reset.
- Verifikasi: `php artisan test --compact --filter=Invoice` hijau + `vendor/bin/pint --dirty --format agent`.

---

# 02 — Client & Invoice Resource (Generate + Manage)

## Objective

Bangun `ClientResource` + `InvoiceResource` Filament: generate invoice (form + repeater items + live total) dan manage (table, filter, view, action status).

## Prerequisites

- Task 01 DONE. Skill `filament-development` aktif. `search-docs` untuk API Filament 5 yang diragukan.

## Steps

1. Generate: `php artisan make:filament-resource Client --generate --no-interaction`, `php artisan make:filament-resource Invoice --generate --no-interaction`. Ikuti struktur hasil generator (Filament 5: `Schemas/`, `Tables/`, `Pages/`).
2. **ClientResource** (sederhana): form `name*, company, email*|email|unique, phone, address, tax_id, notes, is_active`; table `name, company, email, invoices_count, is_active`; filter `is_active`.
3. **InvoiceResource form**:
   - `Select client_id → relationship('client','name') + createOptionForm(nama+email) + required`.
   - `Repeater items → relationship('items')` schema: `description*, qty*|numeric|min:0.01, unit_price*|numeric|min:0` + `live(onBlur:true)` recalc `line_total` via `Get/Set`.
   - `issue_date* default today, due_date* default issue+default_due_days (dari InvoiceSetting::cached())`, validasi `due >= issue`.
   - `discount_type nullable Select, discount_value numeric, tax_rate nullable numeric|min:0|max:100` (label "Opsional").
   - `notes/terms` prefilled dari settings. `invoice_number` readonly auto (diisi saat create via NumberGenerator, bukan input user).
   - Simpan via Calculator: `mutateFormDataBeforeCreate` hitung + generate nomor dalam transaction.
4. **Table**: kolom `invoice_number (searchable), client.name, issue_date, due_date, grand_total money IDR, amount_due, status badge`; `SelectFilter status`, `Filter overdue (due<today & status in sent,partially_paid)`, `TrashedFilter`; actions: `View, Edit (visible jika status in draft,sent), Delete (non-admin dibatasi Shield nanti), SendEmail (task 05), DownloadPdf (task 04), Duplicate (clone + nomor baru)`.
5. **View page**: Infolist header (company dari settings + client), tabel items, totals, timeline payments, header actions status: `MarkSent (draft→sent + set sent_at), MarkCancelled (alasan via modal, bukan delete), Duplicate`.
6. Larang edit items jika `status not in [draft]` (kecuali super_admin — cek via `auth()->user()->hasRole()` setelah task 07; untuk sekarang hard-guard draft-only).

## Acceptance Criteria

- [ ] Create invoice minimal 1 item; total sesuai Calculator; nomor `INV-YYYY-NNNN` unik.
- [ ] Validasi: 0 item ditolak; `due < issue` ditolak; `qty<=0` ditolak.
- [ ] Edit dikunci saat `sent/paid/overdue` (muncul pesan).
- [ ] Table search/filter/sort + pagination bekerja; View menampilkan totals + payments.

## Tests (Pest + Livewire)

- `php artisan make:test --pest Invoice/ManageInvoiceTest`.
- Cover: `livewire(CreateInvoice)` sukses + kalkulasi DB benar; validasi 0-item & due<issue (`assertHasFormErrors`); edit saat sent ditolak; `assertCanSeeTableRecords`.
- Verifikasi: `php artisan test --compact --filter=ManageInvoice` + pint.

---

# 03 — Payment + DP/Cicil + Print Pay Slip

## Objective

Catat pembayaran (Transfer/Cash/VA/QRIS, bisa DP/cicil) + cetak slip pembayaran.

## Prerequisites

- Task 01–02 DONE. Skill `filament-development`.

## Steps

1. Generate: `php artisan make:filament-resource Payment --generate --no-interaction`. Tambah juga `RelationManager PaymentsRelationManager` di `InvoiceResource` (atau `make:filament-relation-manager InvoiceResource payments amount`).
2. Form payment: `invoice_id (hidden saat dari relation), payment_date* default today, amount*|numeric|min:0.01, method* Select enum, reference, notes (contoh "DP 30%")`.
   - Validasi overpay: `amount <= invoice.amount_due` kecuali `InvoiceSetting::cached()->allow_overpayment`.
   - Larang payment jika invoice `draft` atau `cancelled` (error notifikasi).
3. Logic simpan (dalam transaction): create payment (`payment_number` via NumberGenerator type `payment`) → `InvoiceCalculator::recalculate()` → transisi status sesuai kontrak → set `paid_at` saat lunas.
4. Table payments: `payment_number, invoice.invoice_number, payment_date, amount, method badge, reference`; filter method + date range.
5. Slip: tambah route grup `auth` di `routes/web.php`:
   `GET /payments/{payment}/slip → view('payments.slip')` (cek ownership/policy sederhana: user terautentikasi).
   - Buat `resources/views/payments/slip.blade.php`: kop perusahaan (dari settings), no slip, tanggal, invoice ref, client, tabel rincian (total invoice, dibayar payment ini, total terbayar, sisa), metode+referensi, area tanda tangan. CSS print-friendly, tombol Print (`window.print()`) sembunyi saat print.
   - Tambah header action Filament `Print Slip → url(route('payments.slip', $record), shouldOpenInNewTab:true)`.

## Acceptance Criteria

- [ ] DP: bayar 30% → status `partially_paid`, `amount_due` berkurang tepat.
- [ ] Lunasi sisa → status `paid` + `paid_at` terisi.
- [ ] Overpay ditolak saat `allow_overpayment=false`; lolos saat `true`.
- [ ] Payment ke invoice `draft/cancelled` ditolak.
- [ ] `/payments/{id}/slip` tampil benar + tombol print bekerja (cek manual via `get-absolute-url`).

## Tests

- `php artisan make:test --pest Invoice/PaymentTest`.
- Cover: DP partial, pelunasan full, overpay ditolak/diizinkan, draft ditolak, slip route `200` + berisi payment_number.
- Verifikasi: `php artisan test --compact --filter=Payment` + pint.

---

# 04 — Export PDF (DomPDF) + Print Browser

## Objective

Download invoice sebagai PDF (DomPDF) + halaman print browser.

## Prerequisites

- Task 01–02 DONE. **Butuh approval user untuk install**: `barryvdh/laravel-dompdf`. Jika belum di-approve, STOP dan minta approval (jangan lanjut).

## Steps

1. Install (hanya jika approved): `composer require barryvdh/laravel-dompdf`. Konfirmasi versi terinstall via `composer show barryvdh/laravel-dompdf`; cek kompatibel Laravel 13.
2. Buat service: `php artisan make:class Services/InvoicePdfService --no-interaction` dengan ctor promotion `__construct(public InvoiceSetting $settings)` atau tanpa argumen + ambil `InvoiceSetting::cached()` di dalam. Method:
   `generate(Invoice $invoice): string` (return binary PDF) — eager load `client, items, payments`, render `view('invoices.pdf')`, paper A4 portrait, font DejaVu Sans.
3. Buat Blade `resources/views/invoices/pdf.blade.php`:
   - ATURAN DomPDF: hanya table + inline CSS. DILARANG flex/grid/Tailwind/external CSS/JS.
   - Isi: kop (logo base64 dari `storage`, company dari settings), meta (nomor, issue/due, status), bill-to client, tabel items, totals (subtotal, diskon, pajak opsional — sembunyikan baris pajak jika null, grand, terbayar, sisa), terms/footer, tanda tangan.
4. Buat Blade `resources/views/invoices/print.blade.php`: versi browser (boleh Tailwind), `@media print` (sembunyikan tombol), tombol `Cetak / Simpan PDF` (`window.print()`).
5. Routes grup `auth` di `routes/web.php`: `GET /invoices/{invoice}/pdf` (download `INV-....pdf`, content-type application/pdf), `GET /invoices/{invoice}/print` (view).
6. Filament actions di InvoiceResource Table + View: `Download PDF → url(pdf route, new tab)`, `Print → url(print route, new tab)`.

## Acceptance Criteria

- [ ] `/invoices/{id}/pdf` mengunduh file `%PDF`, >5KB, berisi nomor invoice (cek binary contains).
- [ ] Pajak null → baris pajak tidak muncul di PDF.
- [ ] `/invoices/{id}/print` tampil rapi + `window.print()` jalan (cek manual).
- [ ] Logo hilang/tidak ada file tidak membuat PDF error (fallback teks).

## Tests

- `php artisan make:test --pest Invoice/InvoicePdfTest`.
- Cover: response pdf `200` + header `%PDF`; invoice tanpa pajak tetap render; invoice tanpa logo tetap render.
- Verifikasi: `php artisan test --compact --filter=InvoicePdf` + pint.

---

# 05 — Kirim Invoice via Email (PDF Attachment, Queue)

## Objective

Kirim invoice ke client via email dengan lampiran PDF, lewat queue + modal preview.

## Prerequisites

- Task 01, 02, 04 DONE (butuh `InvoicePdfService`). Skill `laravel-best-practices`.

## Steps

1. Buat Mailable: `php artisan make:mail InvoiceMail --no-interaction`. Kontrak:
   - `__construct(public Invoice $invoice, public string $customSubject, public string $customBody)`.
   - `envelope(): Subject = customSubject`; `content(): view('emails.invoices.send') with invoice+body`; `attachments(): [Attachment::fromData(fn() => app(InvoicePdfService::class)->generate($invoice), $invoice->invoice_number.'.pdf')->as(...)->withMime('application/pdf')]`.
2. Buat Blade `resources/views/emails/invoices/send.blade.php`: teks `customBody` (nl2br) + ringkasan (nomor, total, sisa, due) + CTA bayar.
3. Buat Job: `php artisan make:job SendInvoiceEmailJob --no-interaction` — `handle(): Mail::to(client.email)->send(new InvoiceMail(...))` lalu update invoice `sent_at=now`, `status draft→sent` (jangan ubah status lain). Retry 3x, backoff 60s.
4. Filament action `Send Email` (Table row + View header): modal form `to* (default client.email), cc nullable, subject* (default dari settings template + replace {invoice_number},{client_name},{amount_due}), body* textarea (default template)`. Action dispatch job `->after(fn() => Notification::make()->success()->send())`.
5. Template placeholder wajib didukung: `{invoice_number}, {client_name}, {company_name}, {amount_due}, {due_date}`. Helper `strtr()` di Resource + Job.
6. Catatan `.env`: default `MAIL_MAILER=log` (dev cukup). Untuk prod user harus isi SMTP — tulis panduan di output akhir task, jangan commit kredensial.

## Acceptance Criteria

- [ ] Klik Send → job ter-dispatch (cek `jobs` table / queue), email terkirim (dev: cek `laravel.log`), PDF terlampir.
- [ ] Invoice `draft` berubah jadi `sent` + `sent_at` terisi; invoice `sent` tetap `sent` (idempotent).
- [ ] Subject/body placeholder ter-replace semua (tidak ada `{...}` tersisa).

## Tests

- `php artisan make:test --pest Invoice/SendInvoiceEmailTest`.
- Cover: `Mail::fake()` + dispatch action → assert mail sent ke client.email + attachment pdf; status draft→sent; placeholder ter-replace.
- Verifikasi: `php artisan test --compact --filter=SendInvoiceEmail` + pint.

---

# 06 — Settings Hub Sederhana + Widgets + Overdue Scheduler

## Objective

Halaman pengaturan invoice terpusat + dashboard widgets + penanda overdue otomatis.

## Prerequisites

- Task 01–02 DONE. Skill `filament-development`.

## Steps

1. Buat Hub + Page:
   - `php artisan make:filament-page SettingsHub --no-interaction` (jadikan navigasi grup "Settings", icon gear).
   - `php artisan make:filament-page InvoiceSettingsPage --no-interaction` — form Section:
     - Perusahaan: `company_name*, address, phone, email|email, logo upload (disk public, visibility public, image, max 2MB)`.
     - Penomoran: `invoice_prefix* default INV, payment_prefix* default PAY, yearly_reset bool default true` (readonly true + helper "dikunci yearly").
     - Default: `default_due_days* int min:0, default_tax_rate nullable numeric 0-100, default_terms textarea, default_notes textarea, allow_overpayment toggle`.
     - Email: `email_subject_template*, email_body_template* textarea (daftar placeholder di helper)`.
   - Mount: `fillForm(InvoiceSetting::cached()->toArray())`; save: `update + Cache::forget + Notification success`. Akses dibatasi Shield (task 07); sementara `canAccess = auth()->check()`.
2. Hubungkan: form Invoice (due default, terms/notes prefill), NumberGenerator (prefix), email subject/body, PDF kop — semua baca `InvoiceSetting::cached()`. Tidak boleh ada hardcode nama perusahaan di Blade.
3. Widgets:
   - `php artisan make:filament-widget InvoiceStatsOverview --stats-overview --no-interaction`: stat `Outstanding (sum amount_due status sent,partially_paid,overdue), Overdue (sum + count), Paid Bulan Ini, Draft Count`. Format `Rp` (`number_format`), `color()` sesuai kondisi, `pollingInterval null`.
   - `php artisan make:filament-widget RevenueChart --chart --no-interaction`: line/bar 12 bulan (paid per bulan dari `payments.payment_date`).
   - `php artisan make:filament-widget OverdueInvoicesTable --table --no-interaction`: top 10 overdue (invoice_number, client, due, amount_due) + link ke View.
   - Daftarkan di `AdminPanelProvider->widgets()` atau Dashboard (cek file Dashboard yang ada).
4. Scheduler overdue: `php artisan make:command MarkOverdueInvoices --no-interaction` (`signature invoices:mark-overdue`): update `status in (sent,partially_paid) + due_date < today → overdue`. Daftarkan di `routes/console.php` (`Schedule::command(...)->daily()`).

## Acceptance Criteria

- [ ] Ubah settings → tersimpan + form invoice baru memakai default baru.
- [ ] Dashboard menampilkan 3 widget dengan angka yang cocok dengan DB.
- [ ] `php artisan invoices:mark-overdue` mengubah invoice lewat jatuh tempo jadi `overdue`; yang sudah `paid/cancelled` tidak berubah.

## Tests

- `php artisan make:test --pest Invoice/InvoiceSettingsTest, Invoice/OverdueTest`.
- Cover: update settings persist + cache bust; widget stat query benar (buat 1 paid + 1 overdue, assert angka); command overdue.
- Verifikasi: `php artisan test --compact --filter="InvoiceSettings|Overdue"` + pint.

---

# 07 — Filament Shield (Role + Permission)

## Objective

Pasang RBAC Shield: super_admin, staff, viewer + Policy untuk semua Resource invoice.

## Prerequisites

- Task 02, 03, 06 DONE. **Butuh approval install**. Peringatan: Shield minta `php ^8.2|^8.3` sedangkan proyek PHP 8.4 — verifikasi dulu.

## Steps

1. Verifikasi: `php -v`, `composer show filament/filament`, baca `https://filamentphp.com/plugins/bezhansalleh-shield` (dukung Filament 5). Jika `composer require` gagal karena platform, STOP + lapor ke user (opsi: tunggu rilis Shield / override platform — keputusan user).
2. Install (hanya jika approved):
   ```bash
   composer require bezhansalleh/filament-shield
   php artisan vendor:publish --tag="filament-shield-config" --no-interaction
   ```
3. Tambah trait ke model: `use Spatie\Permission\Traits\HasRoles;` di `App\Models\User`. Jalankan migrasi permission yang dipublish (`php artisan migrate`).
4. Setup interaktif (jalankan satu per satu, JANGAN digabung tanpa cek):
   ```bash
   php artisan shield:setup --no-interaction
   php artisan shield:install admin --no-interaction
   php artisan shield:generate --all --no-interaction
   ```
   Lalu buat super admin: `php artisan shield:super-admin --user=1 --no-interaction` (pastikan user id 1 ada; jika tidak, buat dulu via factory/seeder).
5. Matriks permission yang diharapkan (sesuaikan di RoleResource UI `/admin`):
   - `super_admin`: semua (bypass via Gate::before).
   - `staff`: Client C/R/U, Invoice C/R/U + custom action send/download/print/cancel, Payment C/R/U, Settings view saja, TIDAK boleh delete invoice paid / edit settings.
   - `viewer`: hanya view + print/download.
6. Kunci action destruktif: `DeleteAction` invoice berstatus `paid/partially_paid` → `visible(super_admin)` + Policy `delete()` return false kecuali super_admin. Edit InvoiceResource `canEdit` hormati status (kontrak task 02) + role.
7. Pastikan seeder role ikut: `php artisan shield:seeder --generate --option=permissions_via_roles` agar fresh install dapat role dasar.

## Acceptance Criteria

- [ ] `/admin/roles` ada; 3 role terbentuk dengan permission sesuai matriks.
- [ ] Login sebagai viewer → tombol Create/Edit/Delete invoice tidak terlihat / ditolak (403).
- [ ] Staff tidak bisa hapus invoice `paid`; super_admin bisa.
- [ ] Tidak ada regresi: test task 01–06 tetap hijau (policy tidak memblokir super_admin di test — gunakan `actingAs(super_admin)`).

## Tests

- `php artisan make:test --pest Invoice/InvoiceAuthorizationTest`.
- Cover: viewer `403` saat create/update; staff gagal delete paid; super_admin lolos.
- Verifikasi: `php artisan test --compact --filter=InvoiceAuthorization` + pint.

---

# 08 — QA & Release

## Objective

Pastikan seluruh fitur invoice siap rilis: format, static analysis, test penuh, seeder demo.

## Prerequisites

- Task 01–07 DONE. Skill `testing-best-practices`.

## Steps

1. Format: `vendor/bin/pint --dirty --format agent` (WAJIB, bukan `--test`).
2. Static: `composer types:check` (phpstan). Perbaiki error level proyek; jangan tambah baseline tanpa approval.
3. Test bertahap:
   ```bash
   php artisan test --compact --filter=Invoice
   php artisan test --compact
   ```
   Jika ada yang merah, perbaiki di task asalnya (jangan patch di sini), lalu ulangi.
4. Seeder demo: `php artisan make:seeder InvoiceDemoSeeder --no-interaction` — buat 3 clients, 5 invoices (draft/sent/overdue/paid/partially), 3 payments (termasuk 1 DP), settings default. Pastikan idempotent-ish (cek `firstOrCreate` agar tidak duplikat saat di-rerun). Daftarkan pemanggilan di `DatabaseSeeder` HANYA untuk local (guard `app()->isLocal()`).
5. Manual checklist (tulis hasilnya di output akhir):
   - [ ] Buat invoice → download PDF → kirim email (cek `laravel.log` di dev) → bayar DP → lunasi → status `paid`.
   - [ ] Cetak invoice + slip (tombol print).
   - [ ] Widget angka cocok dengan DB (`database-query` SUM pembanding).
   - [ ] Login tiap role (viewer/staff/super_admin) sesuai matriks task 07.
   - [ ] Scheduler `invoices:mark-overdue` terdaftar (`php artisan schedule:list`).
6. Dokumentasi rilis singkat untuk user: kredensial SMTP yang harus diisi, cara buat super_admin, cara jalanin queue (`composer run dev` sudah include queue).

## Acceptance Criteria

- [ ] `pint` bersih, `phpstan` tanpa error baru, `php artisan test --compact` hijau penuh.
- [ ] `php artisan db:seed --class=InvoiceDemoSeeder` sukses di fresh DB.
- [ ] Semua 8 fitur request user terverifikasi manual (tulis bukti per fitur).

## Tests

- Tidakambah test baru kecuali menemukan bug (ikuti aturan coverage task asal).
- Perintah final: `php artisan test --compact` (minta user menjalankannya juga jika agent tidak bisa).

