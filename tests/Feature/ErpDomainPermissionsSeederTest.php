<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Permission;
use Modules\ERP\Database\Seeders\ERPDatabaseSeeder;

uses(RefreshDatabase::class);

it('seeds e-invoice domain permissions for the invoice model only', function (): void {
    $this->seed(ERPDatabaseSeeder::class);

    expect(Permission::query()->where('name', 'default.erp_invoices.post')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'default.erp_invoices.unpost')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'default.erp_invoices.submitEInvoice')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'default.erp_invoices.refreshEInvoice')->exists())->toBeTrue();
});

it('does not seed e-invoice permissions for non-invoice models', function (): void {
    $this->seed(ERPDatabaseSeeder::class);

    expect(Permission::query()->where('name', 'default.erp_journal_entries.reverse')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'default.erp_journal_entries.submitEInvoice')->exists())->toBeFalse();
});

it('seeds posting only for the two documents that are posted', function (): void {
    $this->seed(ERPDatabaseSeeder::class);

    // A fiscal period closes, a quotation and a sales order move through their
    // own status enum, and a journal entry is posted by the invoice that
    // produced it. None of them is dispatched through `post`, so none of them
    // carries the permission.
    $dead = [
        'default.erp_document_sequences.post',
        'default.erp_document_sequences.unpost',
        'default.erp_fiscal_periods.post',
        'default.erp_fiscal_periods.unpost',
        'default.erp_journal_entries.post',
        'default.erp_journal_entries.unpost',
        'default.erp_quotations.post',
        'default.erp_quotations.unpost',
        'default.erp_sales_orders.post',
        'default.erp_sales_orders.unpost',
    ];

    expect(Permission::query()->whereIn('name', $dead)->pluck('name')->all())->toBe([])
        ->and(Permission::query()->where('name', 'default.erp_invoices.post')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'default.erp_delivery_notes.post')->exists())->toBeTrue();
});

it('seeds Phase 2A domain permissions for fiscal and commercial models', function (): void {
    $this->seed(ERPDatabaseSeeder::class);

    expect(Permission::query()->where('name', 'default.erp_fiscal_periods.close')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'default.erp_fiscal_periods.reopen')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'default.erp_fiscal_years.close')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'default.erp_journal_entries.reverse')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'default.erp_sales_orders.amend')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'default.erp_invoices.force_post')->exists())->toBeTrue();
});

it('does not seed force_post on non-invoice models', function (): void {
    $this->seed(ERPDatabaseSeeder::class);

    expect(Permission::query()->where('name', 'default.erp_sales_orders.force_post')->exists())->toBeFalse()
        ->and(Permission::query()->where('name', 'default.erp_delivery_notes.force_post')->exists())->toBeFalse();
});

it('seeds extended admin domain permissions for guarded ERP operations', function (): void {
    $this->seed(ERPDatabaseSeeder::class);

    expect(Permission::query()->where('name', 'default.erp_tax_codes.supersede')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'default.erp_companies.switch_context')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'default.erp_document_sequences.reserve')->exists())->toBeTrue();
});
