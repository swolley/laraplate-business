<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Enums\CoreTables;
use Modules\ERP\Enums\ERPTables;
use Spatie\Permission\PermissionRegistrar;

/**
 * Drop `post` and `unpost` on the five models that are never posted.
 *
 * Posting is not a generic document state in ERP. An invoice and a delivery note
 * are posted, and both have a registered handler in
 * {@see Modules\ERP\Services\DomainActions\ErpDomainActionRegistrar} and a
 * Filament action behind them. A fiscal period opens and closes, a quotation and
 * a sales order move through their own status enum, a document sequence has no
 * posting at all, and a journal entry is posted as a consequence of posting the
 * invoice that produced it, never on its own.
 *
 * The ten names below were seeded all the same, and no code could reach them:
 * {@see Modules\Core\Services\Crud\DomainActionDispatcher} resolves the handler
 * before authorizing, so an unregistered action is a 404 and the permission is
 * never consulted. What they did do was appear on the role screen as if posting a
 * quotation were a governed operation.
 *
 * `permission:refresh` prunes only the verbs it generates itself, so a dead
 * domain verb has to be dropped explicitly. Grants and ACLs go with each row
 * through their cascading foreign keys; neither restricted anything, since an
 * ACL is applied by permission name and no code ever asked for these.
 */
return new class extends Migration
{
    /**
     * Operations dropped on every table listed in {@see self::TABLES}.
     */
    private const array OPERATIONS = ['post', 'unpost'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions_table = (string) config('permission.table_names.permissions', CoreTables::Permissions->value);

        if (! Schema::hasTable($permissions_table) || ! Schema::hasColumn($permissions_table, 'table_name')) {
            return;
        }

        $connection = app('db')->connection();

        // Matched on the table and the trailing operation rather than on the whole
        // name: the connection segment differs per installation, and a `LIKE`
        // pattern would read the underscores in the table names as wildcards.
        $suffixes = array_map(static fn (string $operation): string => '.' . $operation, self::OPERATIONS);

        $ids = $connection->table($permissions_table)
            ->whereIn('table_name', $this->tables())
            ->get(['id', 'name'])
            ->filter(static function (object $permission) use ($suffixes): bool {
                foreach ($suffixes as $suffix) {
                    if (str_ends_with((string) $permission->name, $suffix)) {
                        return true;
                    }
                }

                return false;
            })
            ->pluck('id')
            ->all();

        if ($ids === []) {
            return;
        }

        $connection->table($permissions_table)->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible on purpose: recreating the rows would restore permissions
        // with no consumer, and the grants that hung off them are gone with them.
    }

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        return [
            ERPTables::DocumentSequences->value,
            ERPTables::FiscalPeriods->value,
            ERPTables::JournalEntries->value,
            ERPTables::Quotations->value,
            ERPTables::SalesOrders->value,
        ];
    }
};
