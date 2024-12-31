<?php

namespace App\Components\ProcessReportsGrid;

use App\Constants\Container\ProcessesGridSystemMetadata;
use App\Constants\Container\ProcessReportsViews;
use App\Managers\Container\StandaloneProcessManager;
use App\Repositories\Container\ProcessRepository;

/**
 * Class used for obtaining data source for process reports grid
 * 
 * @author Lukas Velek
 */
class ProcessReportsGridDataSourceHelper {
    private StandaloneProcessManager $spm;

    /**
     * Class constructor
     * 
     * @param ProcessRepository $processRepository
     */
    public function __construct(StandaloneProcessManager $spm) {
        $this->spm = $spm;
    }

    /**
     * Composes query
     * 
     * @param string $view View name
     * @param string $currentUserId Current user ID
     * @param string $processType Process type
     */
    public function composeQuery(string $view, string $currentUserId, string $processType) {
        switch($view) {
            case ProcessReportsViews::VIEW_ALL:
                return $this->composeQueryAll($processType);

            case ProcessReportsViews::VIEW_MY:
                return $this->composeQueryMy($processType, $currentUserId);
        }
    }

    /**
     * Composes query for VIEW_ALL
     * 
     * @param string $processType Process type
     */
    private function composeQueryAll(string $processType) {
        $qb = $this->spm->composeQueryForProcessTypeInstances($processType);

        return $qb;
    }

    /**
     * Composes query for VIEW_MY
     * 
     * @param string $processType Process type
     * @param string $currentUserId Current user ID
     */
    private function composeQueryMy(string $processType, string $currentUserId) {
        $qb = $this->spm->composeQueryForProcessTypeInstances($processType);
        $qb->andWhere('authorUserId = ?', [$currentUserId]);

        return $qb;
    }

    /**
     * Gets metadata for appending for given view
     * 
     * @param string $view View name
     */
    public function getMetadataToAppendForView(string $view) {
        return match($view) {
            ProcessReportsViews::VIEW_ALL => [
                ProcessesGridSystemMetadata::AUTHOR_USER_ID,
                ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID,
                ProcessesGridSystemMetadata::DATE_CREATED,
                ProcessesGridSystemMetadata::STATUS
            ],
            ProcessReportsViews::VIEW_MY => [
                ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID,
                ProcessesGridSystemMetadata::DATE_CREATED,
                ProcessesGridSystemMetadata::STATUS
            ]
        };
    }
}

?>