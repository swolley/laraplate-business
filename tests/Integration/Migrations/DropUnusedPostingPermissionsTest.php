<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\Enums\CoreTables;

uses(RefreshDatabase::class);

/**
 * `post` and `unpost` were seeded on five models that are never posted. The
 * migration drops those ten rows and leaves the invoice and the delivery note,
 * the two documents that really are posted, untouched.
 */
function insertPostingPermission(string $name): int
{
    return (int) DB::table(CoreTables::Permissions->value)->insertGetId([
        'name' => $name,
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function runPostingPermissionDrop(): void
{
    $migration = require module_path('ERP', 'database/migrations/2026_09_07_000000_drop_unused_posting_permissions.php');

    $migration->up();
}

it('drops posting on every model that is not posted', function (): void {
    $dead = [];

    foreach (['erp_document_sequences', 'erp_fiscal_periods', 'erp_journal_entries', 'erp_quotations', 'erp_sales_orders'] as $table) {
        $dead[] = insertPostingPermission("default.{$table}.post");
        $dead[] = insertPostingPermission("default.{$table}.unpost");
    }

    runPostingPermissionDrop();

    expect(DB::table(CoreTables::Permissions->value)->whereIn('id', $dead)->count())->toBe(0);
});

it('leaves the invoice and the delivery note posting alone', function (): void {
    $survivors = [
        insertPostingPermission('default.erp_invoices.post'),
        insertPostingPermission('default.erp_invoices.unpost'),
        insertPostingPermission('default.erp_delivery_notes.post'),
        insertPostingPermission('default.erp_delivery_notes.unpost'),
    ];

    runPostingPermissionDrop();

    expect(DB::table(CoreTables::Permissions->value)->whereIn('id', $survivors)->count())->toBe(4);
});

/**
 * The five models keep the verbs they really answer to: a fiscal period closes
 * and reopens, a journal entry reverses, a sales order is amended.
 */
it('leaves the live verbs on the same tables alone', function (): void {
    $survivors = [
        insertPostingPermission('default.erp_fiscal_periods.close'),
        insertPostingPermission('default.erp_fiscal_periods.reopen'),
        insertPostingPermission('default.erp_journal_entries.reverse'),
        insertPostingPermission('default.erp_sales_orders.amend'),
        insertPostingPermission('default.erp_quotations.unlock'),
        insertPostingPermission('default.erp_document_sequences.reset'),
    ];

    runPostingPermissionDrop();

    expect(DB::table(CoreTables::Permissions->value)->whereIn('id', $survivors)->count())->toBe(6);
});

it('takes the grants and ACLs hanging off a dropped row', function (): void {
    $role_id = DB::table(CoreTables::Roles->value)->insertGetId([
        'name' => 'erp_posting_role',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $id = insertPostingPermission('default.erp_quotations.post');

    DB::table(CoreTables::RoleHasPermissions->value)->insert([
        'permission_id' => $id,
        'role_id' => $role_id,
    ]);

    $acl_id = DB::table(CoreTables::Acls->value)->insertGetId([
        'permission_id' => $id,
        'role_id' => $role_id,
        'unrestricted' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    runPostingPermissionDrop();

    expect(DB::table(CoreTables::RoleHasPermissions->value)->where('permission_id', $id)->exists())->toBeFalse()
        ->and(DB::table(CoreTables::Acls->value)->where('id', $acl_id)->exists())->toBeFalse();
});

/**
 * Another module's table is not this migration's to touch, however it spells the
 * verb.
 */
it('leaves a non-ERP table alone', function (): void {
    $foreign_id = insertPostingPermission('default.cms_contents.post');

    runPostingPermissionDrop();

    expect(DB::table(CoreTables::Permissions->value)->where('id', $foreign_id)->exists())->toBeTrue();
});
