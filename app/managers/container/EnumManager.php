<?php

namespace App\Managers\Container;

use App\Constants\Container\CustomMetadataTypes;
use App\Core\Datatypes\ArrayList;
use App\Core\DB\DatabaseRow;
use App\Entities\ContainerEntity;
use App\Enums\AEnumForMetadata;
use App\Enums\InvoiceCompaniesEnum;
use App\Enums\InvoiceSumCurrencyEnum;
use App\Enums\MetadataUserEnum;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Managers\GroupManager;
use App\Repositories\UserRepository;

/**
 * Enum manager allows to easily use system enums
 * 
 * @author Lukas Velek
 */
class EnumManager extends AManager {
    private UserRepository $userRepository;
    private GroupManager $groupManager;
    private ContainerEntity $container;
    public StandaloneProcessManager $standaloneProcessManager;

    /**
     * Class constructor
     * 
     * @param Logger $logger Instance
     * @param EntityManager $entityManager EntityManager instance
     * @param UserRepository $userRepository UserRepository instance
     * @param GroupManager $groupManager GroupManager instance
     * @param ContainerEntity $container Container DB row
     */
    public function __construct(
        Logger $logger,
        EntityManager $entityManager,
        UserRepository $userRepository,
        GroupManager $groupManager,
        ContainerEntity $container
    ) {
        parent::__construct($logger, $entityManager);

        $this->userRepository = $userRepository;
        $this->groupManager = $groupManager;
        $this->container = $container;
    }

    /**
     * Returns system enum values formatted for use in FormBuilder2 selects for given $metadata type
     * 
     * @param DatabaseRow $metadata Metadata DB row
     * @return array System enum values
     */
    public function getMetadataEnumValuesByMetadataTypeForSelect(DatabaseRow $metadata) {
        $values = $this->getMetadataEnumValuesByMetadataType($metadata);
        
        $values = $this->processEnumValuesToFormValues($values);

        return $values;
    }

    /**
     * Returns system enum values unformatted
     * 
     * @param DatabaseRow $metadata Metadata DB row
     * @return ?ArrayList System enum values
     */
    public function getMetadataEnumValuesByMetadataType(DatabaseRow $metadata) {
        $enum = $this->getEnumByType($metadata->type);

        if($enum === null) {
            return null;
        }
        
        return $enum->getAll();
    }

    /**
     * Returns an instance of system enum by type
     * 
     * @param int $type Metadata type
     * @return ?AEnumForMetadata System enum
     */
    private function getEnumByType(int $type) {
        switch($type) {
            case CustomMetadataTypes::SYSTEM_USER:
                return $this->getSystemUsersEnum();

            case CustomMetadataTypes::SYSTEM_INVOICE_SUM_CURRENCY:
                return $this->getInvoiceSumCurrencyEnum();

            case CustomMetadataTypes::SYSTEM_INVOICE_COMPANIES:
                return $this->getInvoiceCompaniesEnum();

            default:
                return null;
        }
    }

    /**
     * Returns an instance of InvoiceCompaniesEnum containing available invoice companies
     */
    private function getInvoiceCompaniesEnum(): InvoiceCompaniesEnum {
        return new InvoiceCompaniesEnum($this->standaloneProcessManager);
    }

    /**
     * Returns an instance of InvoiceSumCurrencyEnum containing available invoice sum currencies
     */
    private function getInvoiceSumCurrencyEnum(): InvoiceSumCurrencyEnum {
        return new InvoiceSumCurrencyEnum();
    }

    /**
     * Returns an instance of MetadataUserEnum containing available users in container
     */
    private function getSystemUsersEnum(): MetadataUserEnum {
        return new MetadataUserEnum($this->userRepository, $this->groupManager, $this->container);
    }

    /**
     * Processes system enum values to FormBuilder2 select values
     * 
     * @param ArrayList $enumValues System enum values
     * @return array Formatted system enum values
     */
    private function processEnumValuesToFormValues(ArrayList $enumValues) {
        $values = [];
        $toSortArray = [];
        $skipped = [];
        foreach($enumValues->getAll() as $entityId => $data) {
            $key = $data[AEnumForMetadata::KEY];
            $title = $data[AEnumForMetadata::TITLE];

            if($entityId !== 'null') {
                $toSortArray[$key] = $entityId;
            } else {
                $skipped[$key] = $entityId;
            }

            $values[$entityId] = $title;
        }

        ksort($toSortArray);

        $sortedValues = [];
        foreach($toSortArray as $key => $entityId) {
            $sortedValues[] = [
                'value' => $entityId,
                'text' => $values[$entityId]
            ];
        }

        foreach($skipped as $key => $entityId) {
            array_unshift($sortedValues, [
                'value' => $entityId,
                'text' => $values[$entityId]
            ]);
        }

        return $sortedValues;
    }
}

?>