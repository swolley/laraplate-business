<?php

declare(strict_types=1);

namespace Modules\ERP\Authorization;

use Modules\Core\Authorization\Contracts\DeclaresPermissions;
use Modules\ERP\Models\BankStatement;
use Modules\ERP\Models\Company;
use Modules\ERP\Models\DeliveryNote;
use Modules\ERP\Models\DocumentSequence;
use Modules\ERP\Models\FiscalPeriod;
use Modules\ERP\Models\FiscalYear;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\JournalEntry;
use Modules\ERP\Models\PaymentRequest;
use Modules\ERP\Models\PaymentRun;
use Modules\ERP\Models\Quotation;
use Modules\ERP\Models\ReturnOrder;
use Modules\ERP\Models\SalesOrder;
use Modules\ERP\Models\SupplierReturn;
use Modules\ERP\Models\Task;
use Modules\ERP\Models\TaxCode;
use Override;

/**
 * ERP domain permissions.
 *
 * Most of these back a domain action in {@see \Modules\ERP\Services\DomainActions\ErpDomainActionRegistrar};
 * the rest gate a Filament action only ({@see \Modules\ERP\Policies\ERPModelPolicy}
 * checks them either way), which is why the declaration is a list of its own
 * rather than something derived from the action registry.
 *
 * `post` and `unpost` belong to `Invoice` and `DeliveryNote` alone. Posting is
 * not a generic document state here: a fiscal period opens and closes, a
 * quotation and a sales order move through their own status enum, and a journal
 * entry is posted as a consequence of posting the invoice that produced it,
 * never on its own. Declaring the pair on those models seeded a permission no
 * code could ever consult — {@see \Modules\Core\Services\Crud\DomainActionDispatcher}
 * resolves the handler before authorizing, so an unregistered action is a 404
 * and the permission is never reached.
 */
final class ERPPermissions implements DeclaresPermissions
{
    #[Override]
    public static function operations(): array
    {
        return [
            BankStatement::class => ['import_file'],
            Company::class => ['switch_context'],
            DeliveryNote::class => ['post', 'unpost'],
            DocumentSequence::class => ['reset', 'reserve'],
            FiscalPeriod::class => ['close', 'reopen'],
            FiscalYear::class => ['close'],
            Invoice::class => ['post', 'unpost', 'submitEInvoice', 'refreshEInvoice', 'force_post'],
            JournalEntry::class => ['reverse'],
            PaymentRequest::class => ['send'],
            PaymentRun::class => ['export_sepa', 'export_cbi_bonifici'],
            Quotation::class => ['unlock', 'create_revision'],
            ReturnOrder::class => ['approve', 'complete', 'cancel', 'reverse_processed', 'create_credit_note'],
            SalesOrder::class => ['amend'],
            SupplierReturn::class => ['approve', 'complete', 'cancel', 'reverse_processed', 'create_debit_note'],
            Task::class => ['export_ics'],
            TaxCode::class => ['supersede'],
        ];
    }

    #[Override]
    public static function excludedModels(): array
    {
        return [];
    }
}
