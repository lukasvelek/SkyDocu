<?php

namespace App\Modules\UserModule;

use App\Exceptions\GeneralException;

class FileStoragePresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('FileStoragePresenter', 'File storage');
    }

    /**
     * Handles file download
     */
    public function handleDownload() {
        $hash = $this->httpRequest->get('hash');

        if($hash === null) {
            throw new GeneralException('No hash is given.');
        }

        $file = $this->fileStorageManager->getFileByHash($hash);

        $this->app->forceDownloadFile($file->filepath);
    }
}

?>