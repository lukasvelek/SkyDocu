<?php

namespace App\Constants\Container\Processes;

use App\Constants\AConstant;

class InvoiceCustomMetadata extends AConstant {
    public const COMPANY = 'Invoices_Company';
    public const SUM = 'Invoices_Sum';
    public const INVOICE_NO = 'Invoices_InvoiceNo';
    public const SUM_CURRENCY = 'Invoices_SumCurrency';

    public static function toString($key): ?string {
        return match($key) {
            default => null,
            self::COMPANY => 'Company',
            self::SUM => 'Sum',
            self::INVOICE_NO => 'Invoice No.',
            self::SUM_CURRENCY => 'Sum currency'
        };
    }
}

?>