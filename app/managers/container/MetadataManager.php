<?php

namespace App\Managers\Container;

use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\MetadataRepository;

class MetadataManager extends AManager {
    private MetadataRepository $mr;

    public function __construct(Logger $logger, EntityManager $em, MetadataRepository $mr) {
        parent::__construct($logger, $em);

        $this->mr = $mr;
    }
    
    public function createNewMetadata(string $title, string $guiTitle, int $type, ?string $defaultValue, bool $isRequired) {
        $metadataId = $this->createId(EntityManager::C_CUSTOM_METADATA);

        $data = [
            'title' => $title,
            'guiTitle' => $guiTitle,
            'type' => $type,
            'isRequired' => ($isRequired ? 1 : 0),
            'metadataId' => $metadataId
        ];

        if($defaultValue !== null) {
            $data['defaultValue'] = $defaultValue;
        }

        if(!$this->mr->createNewMetadata($data)) {
            throw new GeneralException('Could not create new metadata.');
        }

        return $metadataId;
    }
}

?>