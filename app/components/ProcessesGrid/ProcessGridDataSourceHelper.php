<?php

namespace App\Components\ProcessesGrid;

use App\Constants\Container\ProcessesGridSystemMetadata;
use App\Constants\Container\ProcessGridViews;
use App\Repositories\Container\ProcessRepository;

/**
 * Class used for obtaining data source for process grid
 * 
 * @author Lukas Velek
 */
class ProcessGridDataSourceHelper {
    private ProcessRepository $processRepository;

    /**
     * Class constructor
     * 
     * @param ProcessRepository $processRepository
     */
    public function __construct(ProcessRepository $processRepository) {
        $this->processRepository = $processRepository;
    }

    /**
     * Composes query
     * 
     * @param string $view View name
     * @param string $currentUserId Current user ID
     */
    public function composeQuery(string $view, string $currentUserId) {
        switch($view) {
            case ProcessGridViews::VIEW_ALL:
                return $this->composeQueryAll();

            case ProcessGridViews::VIEW_STARTED_BY_ME:
                return $this->composeQueryStartedByMe($currentUserId);

            case ProcessGridViews::VIEW_WAITING_FOR_ME:
                return $this->composeQueryWaitingForMe($currentUserId);

            case ProcessGridViews::VIEW_WITH_ME:
                return $this->composeQueryWithMe($currentUserId);
        }
    }

    /**
     * Composes query for VIEW_ALL
     */
    private function composeQueryAll() {
        return $this->processRepository->commonComposeQuery();
    }

    /**
     * Composes query for VIEW_STARTED_BY_ME
     * 
     * @param string $currentUserId Current user ID
     */
    private function composeQueryStartedByMe(string $currentUserId) {
        $qb = $this->processRepository->commonComposeQuery();
        $qb->andWhere('authorUserId = ?', [$currentUserId]);
        return $qb;
    }

    /**
     * Composes query for VIEW_WAITING_FOR_ME
     * 
     * @param string $currentUserId Current user ID
     */
    private function composeQueryWaitingForMe(string $currentUserId) {
        $qb = $this->processRepository->commonComposeQuery();
        $qb->andWhere('currentOfficerUserId = ?', [$currentUserId]);
        return $qb;
    }

    /**
     * Composes query for VIEW_WITH_ME
     * 
     * @param string $currentUserId Current user ID
     */
    private function composeQueryWithMe(string $currentUserId) {
        $qb = $this->processRepository->commonComposeQuery();
        $qb->andWhere('workflowUserIds LIKE ?', ['%' . $currentUserId . '%']);
        return $qb;
    }

    /**
     * Gets metadata for appending for given view
     * 
     * @param string $view View name
     */
    public function getMetadataToAppendForView(string $view) {
        return match($view) {
            ProcessGridViews::VIEW_ALL => [
                ProcessesGridSystemMetadata::DOCUMENT_ID,
                ProcessesGridSystemMetadata::TYPE,
                ProcessesGridSystemMetadata::AUTHOR_USER_ID,
                ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID,
                ProcessesGridSystemMetadata::DATE_CREATED,
                ProcessesGridSystemMetadata::STATUS
            ],
            ProcessGridViews::VIEW_STARTED_BY_ME => [
                ProcessesGridSystemMetadata::DOCUMENT_ID,
                ProcessesGridSystemMetadata::TYPE,
                ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID,
                ProcessesGridSystemMetadata::DATE_CREATED,
                ProcessesGridSystemMetadata::STATUS
            ],
            ProcessGridViews::VIEW_WAITING_FOR_ME => [
                ProcessesGridSystemMetadata::DOCUMENT_ID,
                ProcessesGridSystemMetadata::TYPE,
                ProcessesGridSystemMetadata::AUTHOR_USER_ID,
                ProcessesGridSystemMetadata::DATE_CREATED
            ],
            ProcessGridViews::VIEW_WITH_ME => [
                ProcessesGridSystemMetadata::DOCUMENT_ID,
                ProcessesGridSystemMetadata::TYPE,
                ProcessesGridSystemMetadata::AUTHOR_USER_ID,
                ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID,
                ProcessesGridSystemMetadata::DATE_CREATED,
                ProcessesGridSystemMetadata::STATUS
            ],
        };
    }
}

?>