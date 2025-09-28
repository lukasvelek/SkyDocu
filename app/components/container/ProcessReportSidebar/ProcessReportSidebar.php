<?php

namespace App\Components\ProcessReportSidebar;

use App\Components\Sidebar\Sidebar2;
use App\Core\Http\Ajax\Operations\HTMLPageOperation;
use App\Core\Http\Ajax\Requests\PostAjaxRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Managers\Container\ProcessReportManager;

/**
 * This sidebar contains links to all reports available to the logged in user
 * 
 * @author Lukas Velek
 */
class ProcessReportSidebar extends Sidebar2 {
    private ProcessReportManager $processReportManager;

    public function __construct(
        HttpRequest $request,
        ProcessReportManager $processReportManager
    ) {
        parent::__construct($request);
        $this->processReportManager = $processReportManager;

        $this->setComponentName('processReportsSidebar');
    }

    public function startup() {
        parent::startup();

        $par = new PostAjaxRequest($this->httpRequest);
        $par->setComponentUrl($this, 'onLoad');
        $par->addUrlParameter('reportId', $this->httpRequest->get('reportId'));

        $operation = new HTMLPageOperation();
        $operation->setHtmlEntityId('sidebar');
        $operation->setJsonResponseObjectName('sidebarLinks');

        $par->addOnFinishOperation($operation);

        $this->presenter->addScript($par);
        $this->presenter->addScript('
            showLoadingAnimation(\'sidebar\');
            sleep(500);
            ' . $par->getFunctionName() . '();
        ');
    }

    public function actionOnLoad(): JsonResponse {
        $this->getReportLinks();

        $links = '';
        if(!empty($this->links)) {
            $links = implode('<br>', $this->links);
        } else {
            $links = 'No reports available';
        }

        return new JsonResponse(['sidebarLinks' => $links]);
    }

    /**
     * Creates the report links
     */
    private function getReportLinks() {
        $reports = $this->getAvailableReportsForUser();

        foreach($reports as $reportId => $title) {
            $active = false;
            if($this->httpRequest->get('reportId') == $reportId) {
                $active = true;
            }
            $this->addLink($title, $this->createFullURL('User:Reports', 'list', ['reportId' => $reportId]), $active);
        }
    }

    /**
     * Returns an array of all available reports for current user
     */
    private function getAvailableReportsForUser(): array {
        $qb = $this->processReportManager->composeQueryForAllVisibleReports($this->app->currentUser->getId());
        $qb->execute();

        $reports = [];
        while($row = $qb->fetchAssoc()) {
            $reports[$row['reportId']] = $row['title'];
        }

        return $reports;
    }
}