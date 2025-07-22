<?php

namespace App\Managers;

use App\Repositories\ExternalSystemsLogRepository;
use App\Repositories\ExternalSystemsRepository;
use App\Repositories\ExternalSystemsRightsRepository;
use App\Repositories\ExternalSystemsTokenRepository;

class ExternalSystemsManager extends AManager {
    private ExternalSystemsRepository $externalSystemsRepository;
    private ExternalSystemsLogRepository $externalSystemsLogRepository;
    private ExternalSystemsTokenRepository $externalSystemsTokenRepository;
    private ExternalSystemsRightsRepository $externalSystemsRightsRepository;
}