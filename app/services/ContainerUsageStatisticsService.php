<?php

namespace App\Services;

use App\Constants\ContainerStatus;
use App\Core\Application;
use App\Core\FileManager;
use App\Exceptions\AException;
use App\Managers\EntityManager;
use Exception;

class ContainerUsageStatisticsService extends AService {
    public function __construct(Application $app) {
        parent::__construct('ContainerUsageStatistics', $app);
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception $e) {
            $this->logError($e->getMessage());
            $this->serviceStop($e);
            
            throw $e;
        }
    }

    private function innerRun() {
        // Service executes all commands here
        $this->logInfo('Loading all containers...');

        $containers = $this->getAllContainers();

        $this->logInfo('Found ' . count($containers) . ' containers to be analyzed. Retrieving their old SQL log files...');

        $logFiles = $this->getAllHistoricLogFiles($containers);

        $this->logInfo('Found ' . count($logFiles) . ' old SQL log files to be analyzed. Starting analysis...');

        $results = $this->analyzeFiles($logFiles);

        $this->logInfo('Analysis finished. Saving results to the database...');

        $this->saveUsageToDb($results);
    }

    private function getAllContainers() {
        $containersQb = $this->app->containerRepository->composeQueryForContainers();
        $containersQb->andWhere($containersQb->getColumnInValues('status', [ContainerStatus::NOT_RUNNING, ContainerStatus::RUNNING]))
            ->execute();

        $containers = [];
        while($row = $containersQb->fetchAssoc()) {
            $containers[] = $row['containerId'];
        }

        return $containers;
    }

    private function getAllHistoricLogFiles(array $containers) {
        $files = [];

        foreach($containers as $containerId) {
            $this->logInfo('Starting to search for log files for container \'' . $containerId . '\'.');

            if(!FileManager::folderExists(APP_ABSOLUTE_DIR . LOG_DIR . 'containers\\' . $containerId)) {
                $this->logInfo('No files found.');
                continue;
            }

            $containerFiles = FileManager::getFilesInFolder(APP_ABSOLUTE_DIR . LOG_DIR . 'containers\\' . $containerId);

            $this->logInfo('Found ' . count($containerFiles) . 'log files for container \'' . $containerId . '\'.');

            foreach($containerFiles as $filename => $filepath) {
                $this->logInfo('Processing file \'' . $filename . '\' located in \'' . $filepath . '\'.');

                $filenameParts = explode('.', $filename);

                $logType = explode('_', $filenameParts[0])[0];

                if($logType == 'sql-log') {
                    $this->logInfo('File \'' . $filename . '\' is a SQL log file.');

                    $logDate = explode('_', $filenameParts[0])[1];

                    if(strtotime($logDate) < strtotime(date('Y-m-d'))) {
                        $this->logInfo('File \'' . $filename . '\' is older than today.');

                        $files[$containerId][$logDate][$filename] = $filepath;
                    }
                }
            }
        }

        return $files;
    }

    private function analyzeFiles(array $files) {
        $results = [];

        foreach($files as $containerId => $dates) {
            $this->logInfo('Starting analysis for container \'' . $containerId . '\'.');

            foreach($dates as $date => $data) {
                $this->logInfo('Starting analysis for date \'' . $date . '\'.');

                foreach($data as $filename => $filepath) {
                    $this->logInfo('Starting analysis of file \'' . $filepath . '\'');

                    $result = $this->analyzeSingleFile($filepath);

                    $results[$containerId][$date] = $result;
                }
            }
        }

        return $results;
    }

    private function analyzeSingleFile(string $filepath) {
        try {
            $content = FileManager::loadFile($filepath);

            $lines = explode("\r\n", $content);

            $totalTimeTaken = 0.0;
            foreach($lines as $line) {
                // we only want lines with real data not with stack trace
                if(!str_starts_with($line, '[')) {
                    continue;
                }

                $lineParts = explode(' ', $line);
                $timeTaken = substr($lineParts[3], 1);
                $totalTimeTaken = $totalTimeTaken + (float)$timeTaken;
            }

            $averageTimeTaken = ceil($totalTimeTaken / count($lines));

            return [
                'count' => (count($lines) - 1),
                'averageTimeTaken' => $averageTimeTaken,
                'totalTimeTaken' => $totalTimeTaken
            ];
        } catch(AException|Exception $e) {
            return 0;
        }
    }

    private function saveUsageToDb(array $results) {
        foreach($results as $containerId => $data) {
            $this->logInfo('Saving analysis results for container \'' . $containerId . '\'.');
            foreach($data as $date => $measuredData) {
                $totalSqlQueries = $measuredData['count'];
                $averageTimeTaken = (float)$measuredData['averageTimeTaken'];
                $totalTimeTaken = (float)$measuredData['totalTimeTaken'];

                $count = $this->app->containerRepository->getContainerUsageStatisticsForDate($containerId, $date);
                if($count > 0) {
                    $this->logInfo('Analysis results for date \'' . $date . '\' already exist skipping.');

                    continue;
                } else {
                    $this->logInfo('Saving analysis results for date \'' . $date . '\'.');
                }

                try {
                    $this->app->containerRepository->beginTransaction(__METHOD__);
                    
                    $entryId = $this->app->containerManager->entityManager->generateEntityId(EntityManager::CONTAINER_USAGE_STATISTICS);

                    $this->app->containerRepository->insertNewContainerUsageStatisticsEntry($entryId, $containerId, $totalSqlQueries, $averageTimeTaken, $date, $totalTimeTaken);

                    $this->app->containerRepository->commit($this->serviceManager->getServiceUserId(), __METHOD__);

                    $this->logInfo('Analysis results saved to the database.');
                } catch(AException $e) {
                    $this->app->containerRepository->rollback(__METHOD__);

                    $this->logInfo('Could not save analysis results to the database.');
                }
            }
        }
    }
}

?>