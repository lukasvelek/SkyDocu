<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class GroupStandardOperationRights extends AConstant {
    public const CAN_SHARE_DOCUMENTS = 'canShareDocuments';
    public const CAN_EXPORT_DOCUMENTS = 'canExportDocuments';
    public const CAN_VIEW_DOCUMENT_HISTORY = 'canViewDocumentHistory';

    public static function toString($key): ?string {
        return match($key) {
            self::CAN_SHARE_DOCUMENTS => 'Can share documents',
            self::CAN_EXPORT_DOCUMENTS => 'Can export documents',
            self::CAN_VIEW_DOCUMENT_HISTORY => 'Can view document history',
            default => null
        };
    }
}

?>