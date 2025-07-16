<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Core\Http\Ajax\Operations\CustomOperation;
use App\Core\Http\Ajax\Requests\PostAjaxRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\TextResponse;
use App\Exceptions\GeneralException;
use App\Helpers\UnitConversionHelper;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class FileStoragePresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('FileStoragePresenter', 'File storage');

        $this->setSystem();
    }

    public function renderList() {
        // Get files stats
        $par = new PostAjaxRequest($this->httpRequest);

        $par->setUrl($this->createURL('getFilesStats'));

        $op = new CustomOperation();
        $op->addCode('alert(data);');

        $par->addOnFinishOperation($op);
        $par->setResultTypeText();

        $this->addScript($par);
        $this->addScript('
        async function getFileStats() {
            await ' . $par->getFunctionName() . '();
        }
        ');

        $el = HTML::el('a')
            ->class('link')
            ->href('#')
            ->onClick('getFileStats()')
            ->text('Storage stats');

        $this->template->links = $el->toString();
    }

    protected function createComponentStoredFilesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->app->fileStorageRepository->composeQueryForFilesInStorage($this->containerId);

        $grid->createDataSourceFromQueryBuilder($qb, 'fileId');

        $grid->addColumnText('filename', 'Filename');
        $col = $grid->addColumnText('filesize', 'Filesize');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span')
                ->text(UnitConversionHelper::convertBytesToUserFriendly((int)$value));

            return $el;
        };

        $download = $grid->addAction('download');
        $download->setTitle('Download');
        $download->onCanRender[] = function() {
            return true;
        };
        $download->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $url = $this->createURLString('download', ['hash' => $row->hash]);

            $el = HTML::el('a')
                ->class('grid-link')
                ->href($url)
                ->target('_blank')
                ->text('Download');

            return $el;
        };

        return $grid;
    }

    public function actionGetFilesStats() {
        $qb = $this->app->fileStorageRepository->composeQueryForFilesInStorage($this->containerId);
        $qb->execute();

        $size = 0;
        $count = 0;
        while($row = $qb->fetchAssoc()) {
            $size += (int)$row['filesize'];

            $count++;
        }

        $fileSize = UnitConversionHelper::convertBytesToUserFriendly($size);

        $text = 'Total file size: ' . $fileSize . "\r\n";
        $text .= 'Number of files: ' . $count;

        return new TextResponse($text);
    }

    public function handleDownload() {
        $hash = $this->httpRequest->get('hash');

        if($hash === null) {
            throw new GeneralException('No hash is given.');
        }

        $file = $this->app->fileStorageManager->getFileByHash($hash, $this->containerId);

        $this->app->forceDownloadFile($file->filepath);
    }
}

?>