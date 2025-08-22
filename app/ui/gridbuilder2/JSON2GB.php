<?php

namespace App\UI\GridBuilder2;

use App\Constants\Container\SystemGroups;
use App\Core\Application;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\ProcessHelper;
use App\Managers\Container\GroupManager;
use App\UI\HTML\HTML;
use QueryBuilder\QueryBuilder;

/**
 * Helper that converts JSON to GridBuilder instance
 * 
 * @author Lukas Velek
 */
class JSON2GB {
    /**
     * List of mandatory parameters
     */
    private const MANDATORY_PARAMETERS = [
        'table',
        'columns',
        'primaryKey'
    ];

    /**
     * List of supported column types
     */
    private const COLUMN_TYPES = [
        'user',
        'group',
        'userGroup',
        'text',
        'enum',
        'boolean',
        'datetime'
    ];

    private const CUSTOM_COLUMN_TYPES = [
        'processInstanceData_text',
        'processInstanceData_user',
        'processInstanceData_datetime',
        'processInstanceData_textCombination'
    ];

    /**
     * List of supported optional parameters
     */
    private const OPTIONAL_PARAMETERS = [
        'filter',
        'gridConfig',
        'order'
    ];

    private const GRID_CONFIG_PARAMETERS = [
        'refresh',
        'controls',
        'actions',
        'pagination',
        'export'
    ];

    private array $data;
    private GridBuilder $gb;
    private GroupManager $groupManager;
    private Application $app;

    /**
     * Class constructor
     * 
     * @param GridBuilder $gb GridBuilder instance
     * @param array $data Data array
     * @param GroupManager $groupManager GroupManager instance
     * @param Application $app Application instance
     */
    public function __construct(
        GridBuilder $gb,
        array $data,
        GroupManager $groupManager,
        Application $app
    ) {
        $this->data = $data;
        $this->gb = $gb;
        $this->groupManager = $groupManager;
        $this->app = $app;
    }

    /**
     * Sets up the grid
     */
    private function setup() {
        $this->checkDataForMandatoryParameters();

        $this->processDataSource();

        $this->processColumns();

        $this->processGridConfig();

        $rawData = base64_encode(json_encode($this->data));

        $this->gb->addQueryDependency('reportData', $rawData);
    }

    /**
     * Processes grid configuration
     */
    private function processGridConfig() {
        if(!array_key_exists('gridConfig', $this->data)) {
            return;
        }

        foreach($this->data['gridConfig'] as $param => $value) {
            if(!in_array($param, self::GRID_CONFIG_PARAMETERS)) {
                throw new GeneralException('Unsupported grid configuration parameter \'' . $param . '\'.');
            }

            if($value === true) continue;

            switch($param) {
                case 'refresh':
                    $this->gb->disableRefresh();
                    break;

                case 'controls':
                    $this->gb->disableControls();
                    break;

                case 'actions':
                    $this->gb->disableActions();
                    break;

                case 'pagination':
                    $this->gb->disablePagination();
                    break;

                case 'export':
                    $this->gb->disableExport();
                    break;
            }
        }
    }

    /**
     * Adds columns to the grid
     */
    private function processColumns() {
        foreach($this->data['columns'] as $column) {
            $name = $column['name'];
            $title = $column['title'];
            $type = $column['type'];

            if(!in_array($type, self::COLUMN_TYPES) && !in_array($type, self::CUSTOM_COLUMN_TYPES)) {
                throw new GeneralException('Unsupported column type \'' . $type . '\' for column \'' . $name . '\'.');
            }

            if(in_array($type, self::COLUMN_TYPES)) {
                switch($type) {
                    case 'text':
                        $this->gb->addColumnText($name, $title);
                        break;

                    case 'user':
                        $this->gb->addColumnUser($name, $title);
                        break;

                    case 'enum':
                        if(!array_key_exists('enumClass', $column)) {
                            throw new GeneralException('Column \'' . $name . '\' is of type \'' . $type . '\' but no \'enumClass\' attribute is set.');
                        }

                        $this->gb->addColumnConst($name, $title, $column['enumClass']);
                        break;

                    case 'group':
                        $col = $this->gb->addColumnText($name, $title);
                        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
                            $el = HTML::el('span');
                            try {
                                $group = $this->groupManager->getGroupById($value);
                                $el->text($group->title);
                            } catch(AException $e) {
                                $el->text('#ERROR')
                                    ->title($e->getMessage());
                            }
                            return $el;
                        };
                        break;

                    case 'userGroup':
                        if(!array_key_exists('typeCheckColumnEnum', $column)) {
                            throw new GeneralException('Column \'' . $name . '\' is of type \'' . $type . '\' but no \'typeCheckColumnEnum\' attribute is set.');
                        }
                        if(!array_key_exists('name', $column['typeCheckColumnEnum'])) {
                            throw new GeneralException('Column \'' . $name . '\' is of type \'' . $type . '\' but no \'name\' for \'typeCheckColumnEnum\' attribute is set.');
                        }
                        if(!array_key_exists('userKey', $column['typeCheckColumnEnum'])) {
                            throw new GeneralException('Column \'' . $name . '\' is of type \'' . $type . '\' but no \'userKey\' for \'typeCheckColumnEnum\' attribute is set.');
                        }
                        if(!array_key_exists('groupKey', $column['typeCheckColumnEnum'])) {
                            throw new GeneralException('Column \'' . $name . '\' is of type \'' . $type . '\' but no \'groupKey\' for \'typeCheckColumnEnum\' attribute is set.');
                        }

                        $col = $this->gb->addColumnText($name, $title);
                        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($column) {
                            $el = HTML::el('span');

                            if($row->{$column['typeCheckColumnEnum']['name']} == $column['typeCheckColumnEnum']['userKey']) {
                                // user

                                try {
                                    $user = $this->app->userManager->getUserById($value);

                                    $el->text($user->getFullname());
                                } catch(AException $e) {
                                    $el->text('#ERROR');
                                }
                            } else if($row->{$column['typeCheckColumnEnum']['name']} == $column['typeCheckColumnEnum']['groupKey']) {
                                // group

                                try {
                                    $group = $this->groupManager->getGroupById($value);

                                    $el->text(SystemGroups::toString($group->title));
                                } catch(AException $e) {
                                    $el->text('#ERROR')
                                        ->title($e->getMessage());
                                }
                            } else {
                                $el->text('#ERROR')
                                    ->title('Undefined key.');
                            }

                            return $el;
                        };
                        break;

                    case 'boolean':
                        $this->gb->addColumnBoolean($name, $title);
                        break;

                    case 'datetime':
                        $this->gb->addColumnDatetime($name, $title);
                        break;
                }
            } else if(in_array($type, self::CUSTOM_COLUMN_TYPES)) {
                switch($type) {
                    case 'processInstanceData_text':
                        if(!array_key_exists('jsonPath', $column)) {
                            throw new GeneralException('Column \'' . $name . '\' is of type \'' . $type . '\' but no \'jsonPath\' attribute is set.');
                        }
                        $col = $this->gb->addColumnText($name, $title);
                        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($column) {
                            $data = unserialize($row->data);

                            $jsonPath = $column['jsonPath'];
                            
                            $_value = ProcessHelper::getInstanceDataByJsonPath($data, $jsonPath);

                            $el = HTML::el('span');

                            $el->title($_value)
                                ->text($_value);

                            return $el;
                        };
                        break;
                    
                    case 'processInstanceData_textCombination':
                        if(!array_key_exists('combinationParts', $column)) {
                            throw new GeneralException('Column \'' . $name . '\' is of type \'' . $type . '\' but no \'combinationParts\' attribute is set.');
                        }
                        $col = $this->gb->addColumnText($name, $title);
                        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($column) {
                            $data = unserialize($row->data);

                            $result = '';

                            $parts = $column['combinationParts'];

                            foreach($parts as $part) {
                                if(!array_key_exists('type', $part)) {
                                    throw new GeneralException('No \'type\' is defined for column \'' . $column['name'] . '\' of type \'' . $column['type'] . '\'.');
                                }
                                $type = $part['type'];
                                
                                switch($type) {
                                    case 'processInstanceData_text':
                                        if(!array_key_exists('jsonPath', $part)) {
                                            throw new GeneralException('No \'jsonPath\' is defined for column \'' . $column['name'] . '\' of type \'' . $column['type'] . '\'.');
                                        }
                                        $jsonPath = $part['jsonPath'];

                                        $result .= ProcessHelper::getInstanceDataByJsonPath($data, $jsonPath);

                                        break;

                                    case 'processInstanceData_enum':
                                        $jsonPath = $part['jsonPath'];
                                        $enumClass = $part['enumClass'];

                                        $valueFromData = ProcessHelper::getInstanceDataByJsonPath($data, $jsonPath);

                                        if(!class_exists($enumClass)) {
                                            throw new GeneralException('No enum \'' . $enumClass . '\' has been found.');
                                        }

                                        $result .= $enumClass::toString($valueFromData);

                                        break;

                                    case 'text':
                                        $result .= $part['value'];
                                        break;
                                }
                            }

                            $el = HTML::el('span');

                            $el->title($result)
                                ->text($result);

                            return $el;
                        };
                        break;

                    case 'processInstanceData_user':
                        break;
                    
                    case 'processInstanceData_datetime':
                        if(!array_key_exists('jsonPath', $column)) {
                            throw new GeneralException('Column \'' . $name . '\' is of type \'' . $type . '\' but no \'jsonPath\' attribute is set.');
                        }
                        $col = $this->gb->addColumnDatetime($name, $title);
                        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($column) {
                            $data = unserialize($row->data);

                            $jsonPath = $column['jsonPath'];

                            $_value = ProcessHelper::getInstanceDataByJsonPath($data, $jsonPath);

                            $el = HTML::el('span');

                            $friendlyValue = DateTimeFormatHelper::formatDateToUserFriendly($_value, $this->app->currentUser->getDateFormat());

                            $el->title($_value)
                                ->text($friendlyValue);

                            return $el;
                        };
                        break;
                }
            }
        }
    }

    /**
     * Processes the data source
     */
    private function processDataSource() {
        $qb = $this->groupManager->groupRepository->getQb(__METHOD__);

        $qb->select(['*'])
            ->from($this->data['table']);

        $this->processProcessFilter($qb);
        $this->processFilters($qb);
        $this->processOrder($qb);

        $this->gb->createDataSourceFromQueryBuilder($qb, $this->data['primaryKey']);
    }

    /**
     * Processes process filter
     * 
     * @param QueryBuilder &$qb QueryBuilder instance
     */
    private function processProcessFilter(QueryBuilder &$qb) {
        if(!array_key_exists('processFilter', $this->data)) {
            return;
        }

        $qb->andWhere('processId = (SELECT processId FROM processes WHERE name = \'' . $this->data['processFilter'] . '\' AND status = 1 LIMIT 1)');
    }

    /**
     * Processes optional filters
     * 
     * @param QueryBuilder &$qb QueryBuilder instance
     */
    private function processFilters(QueryBuilder &$qb) {
        if(!array_key_exists('filter', $this->data)) {
            return;
        }

        $qb->andWhere($this->data['filter']);
    }

    /**
     * Processes optional order
     * 
     * @param QueryBuilder &$qb QueryBuilder instance
     */
    private function processOrder(QueryBuilder &$qb) {
        if(!array_key_exists('order', $this->data)) {
            return;
        }
        
        foreach($this->data['order'] as $order) {
            if(!array_key_exists('name', $order)) {
                throw new GeneralException('No \'name\' attribute is defined for \'order\' attribute.');
            }
            if(!array_key_exists('order', $order)) {
                throw new GeneralException('No \'order\' attribute is defined for \'order\' attribute.');
            }

            $qb->orderBy($order['name'], $order['order']);
        }
    }

    /**
     * Returns the set up GridBuilder instance
     */
    public function getGridBuilder(): GridBuilder {
        $this->setup();

        return $this->gb;
    }

    /**
     * Checks the data array for mandatory parameters
     */
    private function checkDataForMandatoryParameters() {
        foreach(self::MANDATORY_PARAMETERS as $param) {
            if(!array_key_exists($param, $this->data)) {
                throw new GeneralException('Mandatory parameter \'' . $param . '\' is not present in the definition.');
            }
        }
    }
}