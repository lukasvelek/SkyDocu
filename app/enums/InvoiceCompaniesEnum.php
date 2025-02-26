<?php

namespace App\Enums;

use App\Constants\Container\StandaloneProcesses;
use App\Core\Datatypes\ArrayList;
use App\Managers\Container\StandaloneProcessManager;

/**
 * Metadata invoice companies enum
 * 
 * @author Lukas Velek
 */
class InvoiceCompaniesEnum extends AEnumForMetadata {
    private StandaloneProcessManager $standaloneProcessManager;

    /**
     * Class constructor
     * 
     * @param StandaloneProcessManager $standaloneProcessManager
     */
    public function __construct(StandaloneProcessManager $standaloneProcessManager) {
        parent::__construct();

        $this->standaloneProcessManager = $standaloneProcessManager;
    }

    public function getAll(): ArrayList {
        if($this->cache->isEmpty()) {
            $this->cache->add('null', [self::KEY => 'null', self::TITLE => '-']);

            $values = $this->standaloneProcessManager->getProcessMetadataEnumValues(StandaloneProcesses::INVOICE, 'companies');

            foreach($values as $row) {
                $this->cache->add($row->metadataKey, [self::KEY => $row->valueId, self::TITLE => $row->title]);
            }
        }

        return $this->cache;
    }
}

?>