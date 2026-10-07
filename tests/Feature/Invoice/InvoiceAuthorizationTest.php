<?php

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function roleUser(string $role): User
{
    return tap(User::factory()->create(), fn (User $user) => $user->assignRole($role));
}

it('lets viewers list invoices but not create or edit them', function (): void {
    $this->actingAs(roleUser('viewer'));
    $invoice = Invoice::factory()->create();

    get(InvoiceResource::getUrl('index'))->assertOk();
    get(InvoiceResource::getUrl('create'))->assertForbidden();
    get(InvoiceResource::getUrl('edit', ['record' => $invoice]))->assertForbidden();
});

it('lets staff create invoices', function (): void {
    $this->actingAs(roleUser('staff'));

    get(InvoiceResource::getUrl('create'))->assertOk();
});

it('forbids staff from deleting a paid invoice while super admins can', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);
    InvoiceItem::factory()->for($invoice, 'invoice')->create(['qty' => 1, 'unit_price' => 100_000]);
    $invoice->payments()->create([
        'payment_number' => 'PAY-TEST-0001',
        'payment_date' => now()->format('Y-m-d'),
        'amount' => 100_000,
        'method' => 'transfer',
    ]);
    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Paid);

    $this->assertFalse(roleUser('staff')->can('delete', $invoice));
    $this->assertTrue(superAdmin()->can('delete', $invoice));
});

it('denies viewers any modification ability', function (): void {
    $viewer = roleUser('viewer');
    $invoice = Invoice::factory()->create();

    expect($viewer->can('viewAny', Invoice::class))->toBeTrue()
        ->and($viewer->can('view', $invoice))->toBeTrue()
        ->and($viewer->can('create', Invoice::class))->toBeFalse()
        ->and($viewer->can('update', $invoice))->toBeFalse()
        ->and($viewer->can('delete', $invoice))->toBeFalse();
});
