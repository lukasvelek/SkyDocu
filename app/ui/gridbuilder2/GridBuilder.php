<?php

namespace App\UI\GridBuilder2;

use App\Constants\AConstant;
use App\Constants\IBackgroundColorable;
use App\Constants\IColorable;
use App\Core\AjaxRequestBuilder;
use App\Core\Caching\CacheFactory;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Core\Http\Ajax\Operations\HTMLPageOperation;
use App\Core\Http\Ajax\Requests\AAjaxRequest;
use App\Core\Http\Ajax\Requests\PostAjaxRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\GridExportException;
use App\Helpers\ArrayHelper;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\Modules\APresenter;
use App\UI\AComponent;
use App\UI\FormBuilder2\TextInput;
use App\UI\HTML\HTML;
use Exception;
use QueryBuilder\QueryBuilder;

/**
 * Grid builder is a component used to create data grids or tables.
 * 
 * Functions supported:
 * - custom column order definition
 * - custom column value override
 * - automatic value override (users, datetime, etc.)
 * - row actions (info, edit, delete, etc.)
 * - pagination
 * - refreshing
 * - exporting
 * - filtering
 * 
 * @author Lukas Velek
 * @version 2.0
 */
class GridBuilder extends AComponent {
    private const COL_TYPE_TEXT = 'text';
    private const COL_TYPE_DATETIME = 'datetime';
    private const COL_TYPE_BOOLEAN = 'boolean';
    private const COL_TYPE_USER = 'user';

    protected ?QueryBuilder $dataSource;
    protected ?QueryBuilder $fullDataSource;
    protected string $primaryKeyColName;
    /**
     * @var array<string, Column> $columns
     */
    private array $columns;
    private array $columnLabels;
    private ?Table $table;
    private bool $enablePagination;
    private bool $enableExport;
    private int $gridPage;
    private ?int $totalCount;
    private GridHelper $gridHelper;
    private string $gridName;
    private bool $isPrerendered;

    /**
     * Methods called with parameters: DatabaseRow $row, Row $_row, HTML $rowHtml
     * @var array<callback> $onRowRender
     */
    public array $onRowRender;

    /**
     * @var array<Action> $actions
     */
    private array $actions;

    /**
     * @var array<Filter> $filters
     */
    protected array $filters;
    protected array $activeFilters;

    private array $queryDependencies;

    /**
     * Callbacks that modify the data source if no active filter is set.
     * Methods are called with parameters: QueryBuilder &$qb
     * 
     * @var array<callback> $noFilterSqlConditions
     */
    public array $noFilterSqlConditions;

    protected ?QueryBuilder $filledDataSource;

    private bool $hasCheckboxes;
    private array $checkboxHandler;

    protected CacheFactory $cacheFactory;
    private int $resultLimit;

    private array $quickSearchFilter;
    protected ?string $quickSearchQuery;

    protected bool $actionsDisabled;
    protected bool $controlsDisabled;
    protected bool $refreshDisabled;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     */
    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->dataSource = null;
        $this->fullDataSource = null;
        $this->columns = [];
        $this->columnLabels = [];
        $this->enablePagination = true;
        $this->enableExport = false;
        $this->gridPage = 0;
        $this->onRowRender = [];
        $this->actions = [];
        $this->totalCount = null;
        $this->filters = [];
        $this->activeFilters = [];
        $this->gridName = 'MyGrid';
        $this->queryDependencies = [];
        $this->noFilterSqlConditions = [];
        $this->filledDataSource = null;
        $this->hasCheckboxes = false;
        $this->checkboxHandler = [];
        $this->resultLimit = GRID_SIZE;
        $this->isPrerendered = false;
        $this->quickSearchFilter = [];
        $this->quickSearchQuery = null;
        $this->actionsDisabled = false;
        $this->controlsDisabled = false;
        $this->refreshDisabled = false;
    }

    /**
     * Disables grid refresh
     */
    public function disableRefresh() {
        $this->refreshDisabled = true;
    }

    /**
     * Enables grid refresh
     */
    public function enableRefresh() {
        $this->refreshDisabled = false;
    }

    /**
     * Disables grid controls
     */
    public function disableControls() {
        $this->controlsDisabled = true;
    }

    /**
     * Enables grid controls
     */
    public function enableControls() {
        $this->controlsDisabled = false;
    }

    /**
     * Disables grid actions
     */
    public function disableActions() {
        $this->actionsDisabled = true;
    }

    /**
     * Enables grid actions
     */
    public function enableActions() {
        $this->actionsDisabled = false;
    }

    /**
     * Starts up the component
     */
    public function startup() {
        parent::startup();

        $this->gridPage = $this->getGridPage();
    }

    /**
     * Returns the name of the grid filter cache namespace
     * 
     * @return string Cache namespace
     */
    private function getCacheNameForFilter(): string {
        return CacheNames::GRID_FILTER_DATA . '\\' . $this->gridName . '\\' . $this->app->currentUser?->getId();
    }

    /**
     * Retrieves active filters from cache
     */
    private function getActiveFiltersFromCache() {
        $cache = $this->cacheFactory->getCache($this->getCacheNameForFilter());

        $this->activeFilters = $cache->load($this->gridName . $this->app->currentUser?->getId(), function() {
            return $this->activeFilters;
        });
    }

    /**
     * Saves active filters to cache
     */
    private function saveActiveFilters() {
        $cache = $this->cacheFactory->getCache($this->getCacheNameForFilter());

        $cache->save($this->gridName . $this->app->currentUser?->getId(), function() {
            return $this->activeFilters;
        });
    }

    /**
     * Clears active filters (in cache)
     */
    protected function clearActiveFilters() {
        $this->cacheFactory->invalidateCacheByNamespace($this->getCacheNameForFilter());

        $this->activeFilters = [];
    }

    /**
     * Sets the result limit
     * 
     * @param int $limit Result limit
     */
    public function setLimit(int $limit) {
        $this->resultLimit = $limit;
    }

    /**
     * Sets the GridHelper instance
     * 
     * @param GridHelper $gridHelper GridHelper instance
     */
    public function setHelper(GridHelper $gridHelper) {
        $this->gridHelper = $gridHelper;
    }

    /**
     * Returns the GridHelper instance
     * 
     * @return GridHelper GridHelper instance
     */
    public function getHelper() {
        return $this->gridHelper;
    }

    /**
     * Sets the CacheFactory instance
     * 
     * @param CacheFactory $cacheFactory CacheFactory instance
     */
    public function setCacheFactory(CacheFactory $cacheFactory) {
        $this->cacheFactory = $cacheFactory;
    }

    /**
     * Sets the grid name
     * 
     * @param string $name Grid name
     */
    public function setGridName(string $name) {
        $this->gridName = $name;
    }

    /**
     * Enables pagination
     */
    public function enablePagination() {
        $this->enablePagination = true;
    }

    /**
     * Disables pagination
     */
    public function disablePagination() {
        $this->enablePagination = false;
    }

    /**
     * Enables export
     */
    public function enableExport() {
        $this->enableExport = true;
    }

    /**
     * Disables export
     */
    public function disableExport() {
        $this->enableExport = false;
    }

    /**
     * Adds quick search
     * 
     * @param string $colName Database table column name
     * @param string $placeholderText Text that will be displayed as a place holder
     */
    public function addQuickSearch(string $colName, string $placeholderText) {
        $this->quickSearchFilter[] = ['colName' => $colName, 'placeholderText' => $placeholderText];
    }

    /**
     * Adds a query dependency
     * 
     * @param mixed $key Dependency key
     * @param mixed $value Dependency value
     */
    public function addQueryDependency(mixed $key, mixed $value) {
        $this->queryDependencies[$key] = $value;
    }

    /**
     * Adds filter
     * 
     * @param string $key Grid column name
     * @param mixed $value Current value
     * @param array $options Options available for the filter
     * @return Filter Filter instance
     */
    public function addFilter(string $key, mixed $value, array $options) {
        $filter = new Filter($key, $value, $options);
        $this->filters[$key] = &$filter;

        return $filter;
    }

    /**
     * Adds grid action
     * 
     * @param string $name Action name
     * @return Action Action instance
     */
    public function addAction(string $name) {
        $action = new Action($name);
        $this->actions[$name] = &$action;

        return $action;
    }

    /**
     * Adds a column with value taken from a class extending AConstant
     * 
     * @param string $name Colummn name
     * @param ?string $label Column label
     * @param string $constClass ::class of the constant
     * @return Column Column instance
     */
    public function addColumnConst(string $name, ?string $label = null, string $constClass = '') {
        $col = $this->addColumn($name, self::COL_TYPE_TEXT, $label);
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($constClass) {
            $result = null;
            try {
                if(class_exists($constClass)) {
                    if(in_array(AConstant::class, class_parents($constClass))) {
                        $result = $constClass::toString($value);

                        $el = HTML::el('span');
                        $el->text($result);

                        if(in_array(IColorable::class, class_implements($constClass))) {
                            $color = $constClass::getColor($value);

                            $el->style('color', $color);
                        }

                        if(in_array(IBackgroundColorable::class, class_implements($constClass))) {
                            $bgColor = $constClass::getBackgroundColor($value);

                            if($bgColor !== null) {
                                $el->style('background-color', $bgColor)
                                    ->style('border-radius', '10px')
                                    ->style('padding', '5px');
                            }
                        }

                        $result = $el->toString();
                    }
                }
            } catch(Exception $e) {}

            return $result;
        };
        $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) use ($constClass) {
            $result = null;
            try {
                if(class_exists($constClass)) {
                    if(in_array(AConstant::class, class_parents($constClass))) {
                        $result = $constClass::toString($value);
                    }
                }
            } catch(Exception $e) {}

            return $result;
        };

        return $col;
    }

    /**
     * Adds a column representing a user
     * 
     * @param string $name Column name
     * @param ?string $label Column label
     * @return Column Column instance
     */
    public function addColumnUser(string $name, ?string $label = null) {
        return $this->addColumn($name, self::COL_TYPE_USER, $label);
    }

    /**
     * Adds a column representing a boolean value
     * 
     * @param string $name Column name
     * @param ?string $label column label
     * @return Column Column instance
     */
    public function addColumnBoolean(string $name, ?string $label = null) {
        return $this->addColumn($name, self::COL_TYPE_BOOLEAN, $label);
    }

    /**
     * Adds a column representing a datetime
     * 
     * @param string $name Column name
     * @param ?string $label column label
     * @return Column Column instance
     */
    public function addColumnDatetime(string $name, ?string $label = null) {
        return $this->addColumn($name, self::COL_TYPE_DATETIME, $label);
    }

    /**
     * Adds a general column
     * 
     * @param string $name Column name
     * @param ?string $label Column label
     * @return Column Column instance
     */
    public function addColumnText(string $name, ?string $label = null) {
        return $this->addColumn($name, self::COL_TYPE_TEXT, $label);
    }

    /**
     * Adds a column to the grid
     * 
     * @param string $name Column name
     * @param string $type Column type
     * @param ?string $label column label
     * @return Column Column instance
     */
    private function addColumn(string $name, string $type, ?string $label = null) {
        $col = new Column($name);
        $this->columns[$name] = &$col;
        if($label !== null) {
            $this->columnLabels[$name] = $label;
        } else {
            $this->columnLabels[$name] = $name;
        }

        if($type == self::COL_TYPE_DATETIME) {
            $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
                if($value === null) {
                    return '-';
                }

                $html->title(DateTimeFormatHelper::formatDateToUserFriendly($value, DateTimeFormatHelper::ATOM_FORMAT));
                return DateTimeFormatHelper::formatDateToUserFriendly($value);
            };

            $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) {
                return DateTimeFormatHelper::formatDateToUserFriendly($value);
            };
        } else if($type == self::COL_TYPE_USER) {
            $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
                if($value === null) {
                    return $value;
                }
                $user = $this->app->userManager->getUserById($value);
                if($user === null) {
                    return $value;
                } else {
                    return $user->getFullname();
                }
            };

            $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) {
                if($value === null) {
                    return $value;
                }
                $user = $this->app->userManager->getUserById($value);
                if($user === null) {
                    return $value;
                } else {
                    return $user->getUsername();
                }
            };
        } else if($type == self::COL_TYPE_BOOLEAN) {
            $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
                if($value === true || $value == 1) {
                    $el = HTML::el('span')
                            ->style('color', 'green')
                            ->style('background-color', 'lightgreen')
                            ->style('border-radius', '10px')
                            ->style('padding', '5px')
                            ->text('&check;');
                    $cell->setContent($el);
                } else {
                    $el = HTML::el('span')
                            ->style('color', 'red')
                            ->style('background-color', 'pink')
                            ->style('border-radius', '10px')
                            ->style('padding', '5px')
                            ->text('&times;');
                    $cell->setContent($el);
                }

                return $cell;
            };

            $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) {
                if($value === true) {
                    return 'True';
                } else {
                    return 'False';
                }
            };
        }

        return $col;
    }

    /**
     * Creates grid data source from QueryBuilder instance
     * 
     * @param QueryBuilder $qb QueryBuilder instance
     * @param string $primaryKeyColName Name of the column with primary key
     */
    public function createDataSourceFromQueryBuilder(QueryBuilder $qb, string $primaryKeyColName) {
        $this->dataSource = $qb;
        $this->primaryKeyColName = $primaryKeyColName;
    }

    /**
     * Processes grid data source - applies paging and filtering
     * 
     * @param QueryBuilder $qb QueryBuilder instance
     * @return QueryBuilder QueryBuilder instance
     */
    protected function processQueryBuilderDataSource(QueryBuilder $qb) {
        $qb->limit($this->resultLimit)
            ->offset(($this->gridPage * $this->resultLimit));

        if(!empty($this->activeFilters)) {
            foreach($this->activeFilters as $name => $value) {
                if($value == 'null') {
                    continue;
                }
                if(!empty($this->filters[$name]->onSqlExecute)) {
                    foreach($this->filters[$name]->onSqlExecute as $sql) {
                        $sql($qb, $this->filters[$name]);
                    }
                } else {
                    $qb->andWhere($name . ' = ?', [$value]);
                }
            }
        } else {
            if(!empty($this->noFilterSqlConditions)) {
                foreach($this->noFilterSqlConditions as $sql) {
                    $sql($qb);
                }
            }
        }

        if($this->quickSearchQuery !== null) {
            $conditions = [];
            foreach($this->quickSearchFilter as $filter) {
                $conditions[] = $filter['colName'] . ' LIKE :quickSearchQuery';
            }

            if(!empty($conditions)) {
                $tmp = '(' . implode(' OR ', $conditions) . ')';

                $qb->andWhere($tmp)
                    ->setParams([':quickSearchQuery' => '%' . $this->quickSearchQuery . '%']);
            }
        }

        return $qb;
    }

    /**
     * Renders the grid and the template
     * 
     * @return string HTML code
     */
    public function render() {
        if(!$this->isPrerendered) {
            $this->prerender();
        }
        $this->build();

        $template = $this->getTemplate(__DIR__ . '/grid.html');

        if($this->table !== null) {
            $template->grid = $this->table->output();
        } else {
            $template->grid = '<div style="margin: 0px 11px 0px 11px; padding: 5px 0px 5px 0px;">' . $this->createFlashMessage('info', 'No data found.', 0, false, true) . '</div>';
        }

        $template->scripts = $this->createScripts();
        $template->controls = $this->createGridControls();
        $template->filter_modal = '';
        $template->filters = $this->createGridFilterControls();
        $template->grid_name = $this->gridName;
        
        return $template->render()->getRenderedContent();
    }

    /**
     * Prerenders the grid
     */
    public function prerender() {
        parent::prerender();
        if($this->quickSearchQuery !== null) {
            $this->addQueryDependency('query', $this->quickSearchQuery);
        }
        $this->getActiveFiltersFromCache();
        $this->fetchDataFromDb(true);
        $this->isPrerendered = true;
    }

    /**
     * Processes data source and fetches the data from the database
     * 
     * @param bool $explicit True if the data should be fetched from the database explicitly
     * @return QueryBuilder QueryBuilder instance filled with data
     */
    protected function fetchDataFromDb(bool $explicit = false) {
        if($this->filledDataSource === null || $explicit) {
            if($this->dataSource === null) {
                throw new GeneralException('No data source is set.');
            }

            $dataSource = clone $this->dataSource;
    
            $this->processQueryBuilderDataSource($dataSource);
            $this->fullDataSource = $dataSource;
    
            $this->filledDataSource = $dataSource->execute();
        }

        return $this->filledDataSource;
    }

    /**
     * Builds the grid
     *  - creates columns
     *  - creates rows
     *  - creates cells
     *  - creates table
     *  - creates actions
     */
    private function build() {
        $_tableRows = [];

        $_headerRow = new Row();
        $_headerRow->setPrimaryKey('header');
        foreach($this->columns as $colName => $colEntity) {
            $_headerCell = new Cell();
            $_headerCell->setName($colName);
            $_headerCell->setContent($this->columnLabels[$colName]);
            $_headerCell->setHeader();
            $_headerRow->addCell($_headerCell);
        }

        $_tableRows['header'] = $_headerRow;

        $cursor = $this->fetchDataFromDb();

        $rowIndex = 0;
        while($row = $cursor->fetchAssoc()) {
            $row = $this->createDatabaseRow($row);
            $rowId = $row->{$this->primaryKeyColName};

            $_row = new Row();
            $_row->setPrimaryKey($rowId);
            $_row->index = $rowIndex;
            $_row->rowData = $row;

            foreach($this->columns as $name => $col) {
                $_cell = new Cell();
                $_cell->setName($name);

                if(in_array($name, $row->getKeys())) {
                    $content = $row->$name;

                    if(!empty($this->columns[$name]->onRenderColumn)) {
                        foreach($this->columns[$name]->onRenderColumn as $render) {
                            try {
                                $content = $render($row, $_row, $_cell, $_cell->html, $content);
                            } catch(Exception $e) {}
                        }
                    }
                } else {
                    $content = '-';

                    if(!empty($this->columns[$name]->onRenderColumn)) {
                        foreach($this->columns[$name]->onRenderColumn as $render) {
                            try {
                                $content = $render($row, $_row, $_cell, $_cell->html, $content);
                            } catch(Exception $e) {}
                        }
                    }
                }

                if($content === null) {
                    $content = '-';

                    $_cell->setContent($content);
                } else {
                    if($content instanceof Cell) {
                        $_cell = $content;
                    } else {
                        $_cell->setContent($content);
                    }
                }

                $_row->addCell($_cell);
            }

            if(!empty($this->onRowRender)) {
                foreach($this->onRowRender as $render) {
                    try {
                        $render($row, $_row, $_row->html);
                    } catch(Exception $e) {}
                }
            }

            if($this->hasCheckboxes) {
                $rowCheckbox = new RowCheckbox($rowId, $this->componentName . '_onCheckboxCheck(\'' . $rowId . '\')');
                $_row->addCell($rowCheckbox, true);
            }

            $_tableRows[] = $_row;
            $rowIndex++;
        }

        if($this->hasCheckboxes) {
            $_headerCell = new Cell();
            $_headerCell->setName('checkboxes');
            $_headerCell->setHeader();
            $_headerCell->setContent('');
            $_tableRows['header']->addCell($_headerCell, true);
        }

        if(!empty($this->actions) && !$this->actionsDisabled) {
            $maxCountToRender = 0;
            $canRender = [];
            
            foreach($_tableRows as $k => $_row) {
                if($k == 'header') continue;

                $i = 0;
                foreach($this->actions as $actionName => $action) {
                    $cAction = clone $action;

                    foreach($cAction->onCanRender as $render) {
                        try {
                            $result = $render($_row->rowData, $_row, $cAction);

                            if($result == true) {
                                $canRender[$k][$actionName] = $cAction;
                                $i++;
                            } else {
                                $canRender[$k][$actionName] = null;
                            }
                        } catch(Exception $e) {
                            $canRender[$k][$actionName] = null;
                        }
                    }

                    if($i > $maxCountToRender) {
                        $maxCountToRender = $i;
                    }
                }
            }

            $cells = [];
            if(count($this->actions) == $maxCountToRender) {
                foreach($canRender as $k => $actionData) {
                    $_row = &$_tableRows[$k];

                    $actionData = ArrayHelper::reverseArray($actionData);
                    
                    foreach($actionData as $actionName => $action) {
                        if($action instanceof Action) {
                            $cAction = clone $action;
                            $cAction->inject($_row->rowData, $_row, $_row->primaryKey);
                            $_cell = new Cell();
                            $_cell->setName($actionName);
                            $_cell->setContent($cAction->output()->toString());
                            $_cell->setClass('grid-cell-action');
                        } else {
                            $_cell = new Cell();
                            $_cell->setName($actionName);
                            $_cell->setContent('');
                            $_cell->setClass('grid-cell-action');
                        }

                        $cells[$k][$actionName] = $_cell;
                    }
                }
            } else {
                foreach($canRender as $k => $actionData) {
                    $_row = &$_tableRows[$k];

                    $actionData = ArrayHelper::reverseArray($actionData);
                    
                    foreach($actionData as $actionName => $action) {
                        if($action instanceof Action) {
                            $cAction = clone $action;
                            $cAction->inject($_row->rowData, $_row, $_row->primaryKey);
                            $_cell = new Cell();
                            $_cell->setName($actionName);
                            $_cell->setContent($cAction->output()->toString());
                            $_cell->setClass('grid-cell-action');
                            $cells[$k][$actionName] = $_cell;
                        }
                    }
                }
            }

            /**
             * All action names that should be displayed
             */
            $tmp = [];
            foreach($cells as $k => $c) {
                foreach($c as $cell) {
                    if(!in_array($cell->getName(), $tmp)) {
                        $tmp[] = $cell->getName();
                    }
                }
            }

            if(count($tmp) > 0) {
                $_headerCell = new Cell();
                $_headerCell->setName('actions');
                $_headerCell->setContent('Actions');
                $_headerCell->setHeader();
                $_headerCell->setSpan(count($tmp));
                $_tableRows['header']->addCell($_headerCell, true);
            }

            foreach(array_keys($_tableRows) as $k) {
                if($k == 'header') continue;
                if(!array_key_exists($k, $cells)) continue;
                
                $_cells = $cells[$k];

                foreach($tmp as $name) {
                    if(array_key_exists($name, $_cells)) {
                        /**
                         * Action with this name on this row should exist
                         */
                        $_tableRows[$k]->addCell($_cells[$name], true);
                    } else {
                        /**
                         * Action with this name on this row should not exist
                         */
                        $_cell = new Cell();
                        $_cell->setName($name);
                        $_cell->setContent('');
                        $_cell->setClass('grid-cell-action');

                        $_tableRows[$k]->addCell($_cell, true);
                    }
                }
            }
        }

        if(count($_tableRows) == 1) {
            $this->table = null;
        } else {
            $this->table = new Table($_tableRows);
        }
    }

    /**
     * Creates a DatabaseRow instance from $row
     * 
     * @param mixed $row mysqli_result
     * @return DatabaseRow DatabaseRow instance
     */
    protected function createDatabaseRow(mixed $row) {
        $r = new DatabaseRow();

        foreach($row as $k => $v) {
            $r->$k = $v;
        }

        return $r;
    }

    /**
     * Creates grid controls
     * 
     * @return string HTML code
     */
    private function createGridControls() {
        $code = '
            <div class="row">
                <div class="col-md">
                    ' . $this->createGridPagingControl() . '
                </div>
                
                <div class="col-md">
                    ' . $this->createGridPageInfo() . '
                </div>
                
                <div class="col-md" ' . ($this->enableExport ? '' : ' id="right"') . '>
                    ' . $this->createGridRefreshControl() . '
                </div>

                ' . ($this->enableExport ? ('<div class="col-md-2" id="right">' . $this->createGridExportControl() . '</div>') : ('')) . '
            </div>
        ';

        return $code;
    }

    /**
     * Creates necessary JS scripts
     * 
     * @return string HTML code
     */
    private function createScripts() {
        $scripts = [];

        $addScript = function(AjaxRequestBuilder|AAjaxRequest $arb) use (&$scripts) {
            if($arb instanceof AjaxRequestBuilder) {
                $scripts[] = $arb->build();
            } else if($arb instanceof AAjaxRequest) {
                $scripts[] = $arb->build();
            }
        };

        // REFRESH
        $par = new PostAjaxRequest($this->httpRequest);

        $data = [
            'gridPage' => '_page'
        ];

        $args = [
            '_page'
        ];

        if(!empty($this->activeFilters)) {
            foreach($this->activeFilters as $name => $value) {
                $argName = '_' . $name;

                $data[$name] = $argName;
                $args[] = $argName;
            }
        }

        if(!empty($this->queryDependencies)) {
            foreach($this->queryDependencies as $name => $value) {
                $argName = '_' . $name;

                $data[$name] = $argName;
                $args[] = $argName;
            }
        }

        $par->setComponentUrl($this, 'refresh')
            ->setData($data);

        foreach($args as $arg) {
            $par->addArgument($arg);
        }

        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId($this->gridName)
            ->setJsonResponseObjectName('grid');

        $par->addOnFinishOperation($updateOperation);

        $addScript($par);
        $scripts[] = '
            function ' . $this->componentName . '_gridRefresh(' . implode(', ', $args) . ') {
                ' . $par->getFunctionName() . '(' . implode(', ', $args) . ');
            }
        ';

        // PAGINATION
        $par = new PostAjaxRequest($this->httpRequest);

        $data = [
            'gridPage' => '_page'
        ];

        $args = [
            '_page'
        ];

        if(!empty($this->activeFilters)) {
            foreach($this->activeFilters as $name => $value) {
                $argName = '_' . $name;

                $data[$name] = $argName;
                $args[] = $argName;
            }
        }

        if(!empty($this->queryDependencies)) {
            foreach($this->queryDependencies as $name => $value) {
                $argName = '_' . $name;

                $data[$name] = $argName;
                $args[] = $argName;
            }
        }

        $par->setComponentUrl($this, 'page')
            ->setData($data);

        foreach($args as $arg) {
            $par->addArgument($arg);
        }

        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId($this->gridName)
            ->setJsonResponseObjectName('grid');

        $par->addOnFinishOperation($updateOperation);

        $addScript($par);
        $scripts[] = '
            function ' . $this->componentName . '_page(' . implode(', ', $args) . ') {
                ' . $par->getFunctionName() . '(' . implode(', ', $args) . ');
            }
        ';

        // FILTER
        if(!empty($this->filters)) {
            $par = new PostAjaxRequest($this->httpRequest);

            $data = [];
            $args = [];

            foreach($this->filters as $name => $value) {
                $argName = '_' . $name;

                $data[$name] = $argName;
                $args[] = $argName;
            }
    
            if(!empty($this->queryDependencies)) {
                foreach($this->queryDependencies as $name => $value) {
                    $argName = '_' . $name;
    
                    $data[$name] = $argName;
                    $args[] = $argName;
                }
            }

            $par->setComponentUrl($this, 'filter')
                ->setData($data);

            foreach($args as $arg) {
                $par->addArgument($arg);
            }

            $updateOperation = new HTMLPageOperation();
            $updateOperation->setHtmlEntityId($this->gridName)
                ->setJsonResponseObjectName('grid');

            $par->addOnFinishOperation($updateOperation);

            $addScript($par);
            $scripts[] = '
                function ' . $this->componentName . '_filter(' . implode(', ', $args) . ') {
                    ' . $par->getFunctionName() . '(' . implode(', ', $args) . ');
                }
            ';
        }

        // FILTER MODAL
        if(!empty($this->filters)) {
            $scripts[] = '
                    async function ' . $this->componentName . '_processFilterModalOpen() {
                        $("#grid-filter-modal-inner")
                            .css("height", "90%")
                            .css("visibility", "visible")
                            .css("width", "90%");
                    }
            ';
        }

        if(!empty($this->filters) || !empty($this->quickSearchFilter)) {
            $par = new PostAjaxRequest($this->httpRequest);

            $data = [];
            $args = [];
    
            if(!empty($this->queryDependencies)) {
                foreach($this->queryDependencies as $name => $value) {
                    $argName = '_' . $name;
    
                    $data[$name] = $argName;
                    $args[] = $argName;
                }
            }

            $par->setData($data)
                ->setComponentUrl($this, 'filterClear');

            foreach($args as $arg) {
                $par->addArgument($arg);
            }

            $updateOperation = new HTMLPageOperation();
            $updateOperation->setHtmlEntityId($this->gridName)
                ->setJsonResponseObjectName('grid');

            $par->addOnFinishOperation($updateOperation);

            $addScript($par);
            $scripts[] = '
                function ' . $this->componentName . '_filterClear(' . implode(', ', $args) . ') {
                    ' . $par->getFunctionName() . '(' . implode(', ', $args) . ');
                }
            ';
        }

        // QUICK SEARCH
        if(!empty($this->quickSearchFilter)) {
            $par = new PostAjaxRequest($this->httpRequest);

            $data['query'] = '_query';

            $par->setComponentUrl($this, 'quickSearch')
                ->setData($data);

            foreach($args as $arg) {
                $par->addArgument($arg);
            }
            $par->addArgument('_query');

            $op = new HTMLPageOperation();
            $op->setHtmlEntityId($this->gridName)
                ->setJsonResponseObjectName('grid');

            $par->addOnFinishOperation($op);

            $addScript($par);

            $code = '
                function ' . $this->componentName . '_quickSearch(' . implode(', ', $args) . ') {
                    var query = $("#' . $this->componentName . '_search").val();

                    if(query.length == 0) {
                        alert("No data entered.");
                    } else {
                        ' . $par->getFunctionName() . '(' . implode(', ', $args) . (count($args) > 0 ? ', ' : '') . ' query);
                    }
                }
            ';

            $scripts[] = $code;
        }

        // EXPORT MODAL
        if($this->enableExport) {
            $scripts[] = '
                    async function ' . $this->componentName . '_processExportModalOpen() {
                        $("#grid-export-modal-inner")
                            .css("height", "90%")
                            .css("visibility", "visible")
                            .css("width", "90%");
                    }
            ';
        }

        // EXPORT
        if($this->enableExport) {
            $arb = new AjaxRequestBuilder();

            $headerParams = [];
            $fArgs = [];
            foreach($this->filters as $name => $filter) {
                $hName = '_' . $name;
                $headerParams[$name] = $hName;
                $fArgs[] = $hName;
            }

            if(!empty($this->queryDependencies)) {
                foreach($this->queryDependencies as $k => $v) {
                    $pK = '_' . $k;
    
                    $headerParams[$k] = $pK;
                    $fArgs[] = $pK;
                }
            }

            $arb->setMethod()
                ->setComponentAction($this->presenter, $this->componentName . '-exportLimited')
                ->setHeader($headerParams)
                ->setFunctionName($this->componentName . '_exportLimited')
                ->setFunctionArguments($fArgs)
                ->addWhenDoneOperation('if(obj.file) {
                    window.open(obj.file, "_blank");
                }')
            ;

            $addScript($arb);

            $arb->setMethod()
                ->setComponentAction($this->presenter, $this->componentName . '-exportUnlimited')
                ->setHeader($headerParams)
                ->setFunctionName($this->componentName . '_exportUnlimited')
                ->setFunctionArguments($fArgs)
                ->addWhenDoneOperation('if(obj.success) { alert("Your export will be created asynchronously. You can find it in Grid export management section."); }')
            ;

            $addScript($arb);
        }

        // CHECKBOXES
        if($this->hasCheckboxes) {
            $arb = new AjaxRequestBuilder();

            $headerParams = [
                'ids[]' => '_ids'
            ];

            if(array_key_exists('params', $this->checkboxHandler)) {
                foreach($this->checkboxHandler['params'] as $paramName => $paramKey) {
                    $headerParams[$paramName] = $paramKey;
                }
            }

            if(!array_key_exists('isComponent', $this->checkboxHandler)) {
                $arb->setAction($this->checkboxHandler['presenter'], $this->checkboxHandler['action']);
            } else {
                $arb->setComponentAction($this->checkboxHandler['presenter'], $this->componentName . '-' . $this->checkboxHandler['action']);
            }

            $arb->setMethod()
                ->setHeader($headerParams)
                ->setFunctionName($this->componentName . '_onCheckboxCheck')
                ->addBeforeAjaxOperation('
                    const _now = Date.now();
                    _checkboxHandlerTimestamp = _now;

                    const _ids = $("input[type=checkbox]:checked").map(function(_, el) {
                        return $(el).attr("value[]");
                    }).get();

                    if(_ids.length == 0) {
                        ' . $this->componentName . '_processBulkActionsModalClose();
                        return;
                    }

                    $("#modal").show(); ' . $this->componentName . '_processBulkActionsModalOpen(true);

                    await sleep(2000);

                    if(_checkboxHandlerTimestamp != _now) {
                        return;
                    };

                    if(_ids.length == 0) {
                        ' . $this->componentName . '_processBulkActionsModalClose();
                        return;
                    }
                ')
                ->updateHTMLElement('modal', 'modal')
                ->addWhenDoneOperation('_checkboxHandlerTimestamp = null;')
                ->addWhenDoneOperation('
                    $("#modal").show(); ' . $this->componentName . '_processBulkActionsModalOpen(false);
                ')
                ->addCustomArg('_ids')
            ;

            $addScript($arb);

            $scripts[] = '
                    function ' . $this->componentName . '_processBulkActionsModalOpen(_showLoading) {
                        if(_showLoading) {
                            $("#modal").html(\'<div id="bulk-actions-modal-inner" style="visibility: hidden; height: 0px; position: absolute; top: 5%; left: 5%; background-color: rgba(225, 225, 225, 1); z-index: 9999; border-radius: 5px;"></div>\');
                            $("#bulk-actions-modal-inner").html(\'<div id="center" style="margin-top: 20px"><img src="resources/loading.gif" width="64"><br>Loading...</div>\');
                        }

                        $("#bulk-actions-modal-inner")
                            .css("height", "15%")
                            .css("visibility", "visible")
                            .css("width", "90%");
                    }

                    function ' . $this->componentName . '_processBulkActionsModalClose() {
                        $("#modal").html(\'\');
                        $("#modal").hide();
                    }
            ';
        }

        return '<script type="text/javascript">' . implode("\r\n\r\n", $scripts) . '</script>';
    }

    /**
     * Creates control for grid exporting
     * 
     * @return string HTML code
     */
    private function createGridExportControl() {
        return '<button type="button" class="grid-control-button2" onclick="' . $this->componentName . '_processExportModalOpen()">Export</button>';
    }

    /**
     * Creates grid paging information
     * 
     * @return string Paging information
     */
    private function createGridPageInfo() {
        if(!$this->enablePagination) {
            return '';
        }

        $totalCount = $this->getTotalCount();
        $lastPage = (int)ceil($totalCount / $this->resultLimit);

        $lastPageCount = $this->resultLimit * ($this->gridPage + 1);
        if($lastPageCount > $totalCount) {
            $lastPageCount = $totalCount;
        }

        // If the grid is empty, no page exists
        $displayGridPage = $this->gridPage;
        if($lastPage > 0) {
            $displayGridPage++;
        }

        return 'Page ' . $displayGridPage . ' of ' . $lastPage . ' (' . ($this->resultLimit * $this->gridPage) . ' - ' . $lastPageCount . ')';
    }

    /**
     * Creates grid refresh control
     * 
     * @return string HTML code
     */
    private function createGridRefreshControl() {
        if($this->refreshDisabled) {
            return '';
        }

        $args = [$this->gridPage];

        if(!empty($this->activeFilters)) {
            foreach($this->activeFilters as $name => $value) {
                $args[] = '\'' . $value . '\'';
            }
        }

        if(!empty($this->queryDependencies)) {
            foreach($this->queryDependencies as $k => $v) {
                $args[] = '\'' . $v . '\'';
            }
        }

        return '<a class="link" href="#" onclick="' . $this->componentName . '_gridRefresh(' . implode(', ', $args) . ')" title="Refresh grid">Refresh &orarr;</a>';
    }

    /**
     * Creates grid paging control
     * 
     * @return string HTML code
     */
    private function createGridPagingControl() {
        if(!$this->enablePagination) {
            return '';
        }

        $totalCount = $this->getTotalCount();
        $lastPage = (int)ceil($totalCount / $this->resultLimit) - 1;

        $firstPageBtn = $this->createPagingButtonCode(0, '&lt;&lt;', ($this->gridPage == 0));
        $previousPageBtn = $this->createPagingButtonCode(($this->gridPage - 1), '&lt;', ($this->gridPage == 0));
        $nextPageBtn = $this->createPagingButtonCode(($this->gridPage + 1), '&gt;', ($this->gridPage >= $lastPage));
        $lastPageBtn = $this->createPagingButtonCode($lastPage, '&gt;&gt;', ($this->gridPage >= $lastPage));

        return implode('', [$firstPageBtn, $previousPageBtn, $nextPageBtn, $lastPageBtn]);
    }

    /**
     * Creates a paging button code
     * 
     * @param int $page Page to be changed to
     * @param string $text Button text
     * @param bool $disabled True if button is disabled or false if not
     * @return string HTML code
     */
    private function createPagingButtonCode(int $page, string $text, bool $disabled = false) {
        $args = [$page];

        if(!empty($this->activeFilters)) {
            foreach($this->activeFilters as $name => $value) {
                $args[] = '\'' . $value . '\'';
            }
        }

        if(!empty($this->queryDependencies)) {
            foreach($this->queryDependencies as $k => $v) {
                $args[] = '\'' . $v . '\'';
            }
        }

        $description = null;
        switch($text) {
            case '&lt;&lt;':
                $description = 'Go to first page';
                break;

            case '&lt;':
                $description = 'Go to previous page';
                break;

            case '&gt;':
                $description = 'Go to next page';
                break;

            case '&gt;&gt;':
                $description = 'Go to last page';
                break;
        }

        return '<button type="button" class="grid-control-button" onclick="' . $this->componentName . '_page(' . implode(', ', $args) . ')"' . ($disabled ? ' disabled' : '') . ($description !== null ? (' title="' . $description . '"') : '') . '>' . $text . '</button>';
    }

    /**
     * Returns current grid page
     * 
     * @return int Current grid page
     */
    private function getGridPage() {
        $page = 0;

        if($this->httpRequest->query('gridPage') !== null) {
            $page = $this->httpRequest->query('gridPage');
        } else if($this->httpRequest->post('gridPage') !== null) {
            $page = $this->httpRequest->post('gridPage');
        }

        $page = $this->gridHelper->getGridPage($this->gridName, $page);

        return (int)$page;
    }

    /**
     * Returns total entry count
     * 
     * @return int Total entry count
     */
    private function getTotalCount() {
        if($this->totalCount !== null) {
            return $this->totalCount;
        }

        $dataSource = clone $this->fullDataSource;
        
        $dataSource->resetLimit()->resetOffset()->select(['COUNT(*) AS cnt']);
        $dataSource->regenerateSQL();
        $this->totalCount = $dataSource->execute()->fetch('cnt');
        return $this->totalCount;
    }

    /**
     * Creates code for filter controls
     * 
     * @return string HTML code
     */
    private function createGridFilterControls() {
        if((empty($this->filters) && empty($this->quickSearchFilter)) || $this->controlsDisabled) {
            return '';
        }

        $el = HTML::el('span');

        if(!empty($this->filters)) {
            $btn = HTML::el('button')
                ->addAtribute('type', 'button')
                ->onClick($this->componentName . '_processFilterModalOpen()')
                ->id('formSubmit')
                ->text('Filter')
                ->title('Filter')
            ;

            $btns = [
                $btn->toString()
            ];
        }

        $args = [];
        if(!empty($this->queryDependencies)) {
            foreach($this->queryDependencies as $k => $v) {
                $args[] = '\'' . $v . '\'';
            }
        }

        if(!empty($this->activeFilters)) {
            $btn = HTML::el('button')
                    ->addAtribute('type', 'button')
                    ->onClick($this->componentName . '_filterClear(' . implode(', ', $args) . ')')
                    ->id('formSubmit')
                    ->text('Clear filter')
                    ->title('Clear filter')
            ;

            $btns[] = $btn->toString();
        }

        if(!empty($this->quickSearchFilter)) {
            $input = new TextInput($this->componentName . '_search');
            $texts = [];
            foreach($this->quickSearchFilter as $filter) {
                $texts[] = $filter['placeholderText'];
            }
            $placeholderText = implode(', ', $texts);
            $input->setPlaceholder($placeholderText);
            if($this->quickSearchQuery !== null) {
                $input->setValue($this->quickSearchQuery);
            }

            $btns[] = $input->render();

            $btn = HTML::el('button')
                        ->addAtribute('type', 'button')
                        ->onClick($this->componentName . '_quickSearch(' . implode(', ', $args) . ')')
                        ->id('formSubmit')
                        ->text('Search')
                        ->title('Search');

            $btns[] = $btn->toString();

            if($this->quickSearchQuery !== null) {
                $btn = HTML::el('button')
                        ->addAtribute('type', 'button')
                        ->onClick($this->componentName . '_filterClear(' . implode(', ', $args) . ')')
                        ->id('formSubmit')
                        ->text('Clear search')
                        ->title('Clear search')
                ;

                $btns[] = $btn->toString();
            }
        }

        $el->text(implode('&nbsp;', $btns));

        return $el->toString();
    }

    // FILTER MODAL COMPONENT
    /**
     * Creates filter modal instance
     * 
     * @return GridFilter GridFilter instance
     */
    protected function createComponentFilter() {
        $filter = GridFilter::createFromComponent($this);
        $filter->setFilters($this->filters);
        $filter->setGridComponentName($this->componentName);
        $filter->setGridColumns($this->columnLabels);
        $filter->setActiveFilters($this->activeFilters);
        $filter->setQueryDependencies($this->queryDependencies);

        return $filter;
    }

    // EXPORT MODAL COMPONENT
    /**
     * Creates export modal instance
     * 
     * @return GridExportModal GridExportModal instance
     */
    protected function createComponentExport() {
        $gem = new GridExportModal($this);

        $ds = clone $this->dataSource;
        $ds = $this->processQueryBuilderDataSource($ds);
        $gem->setDataSource($ds);
        $gem->setGridQueryDependencies($this->queryDependencies);

        return $gem;
    }

    /**
     * Creates an instance of component from other component
     * 
     * @param AComponent $component Other component
     * @return AComponent
     */
    public static function createFromComponent(AComponent $component) {
        $obj = new self($component->httpRequest);
        $obj->setApplication($component->app);
        $obj->setPresenter($component->presenter);

        return $obj;
    }

    /**
     * Creates an instance of GridExportHandler
     * 
     * @param QueryBuilder $dataSource Grid data source
     * @return GridExportHandler GridExportHandler instance
     */
    protected function createGridExportHandler(QueryBuilder $dataSource) {
        return new GridExportHandler(
            $dataSource,
            $this->primaryKeyColName,
            $this->columns,
            $this->columnLabels,
            $this->presenter->getUserId(),
            $this->app,
            $this->gridName
        );
    }

    // CHECKBOXES
    /**
     * Adds checkboxes to the grid
     * 
     * @param APresenter $presenter Handler presenter
     * @param string $action Handler action
     */
    public function addCheckboxes(APresenter $presenter, string $action) {
        $this->hasCheckboxes = true;
        $this->checkboxHandler = [
            'presenter' => $presenter,
            'action' => $action
        ];
    }

    /**
     * Adds checkboxes to the grid
     * 
     * @param APresenter $presenter Handler presenter
     * @param string $action Handler action
     */
    public function addCheckboxes2(APresenter $presenter, string $componentAction, array $params = []) {
        $this->hasCheckboxes = true;
        $this->checkboxHandler = [
            'presenter' => $presenter,
            'action' => $componentAction,
            'isComponent' => true
        ];

        if(!empty($params)) {
            $this->checkboxHandler['params'] = $params;
        }
    }

    /**
     * Returns data source with applied pagination
     * 
     * @return ?QueryBuilder QueryBuilder or null
     */
    protected function getPagedDataSource() {
        if($this->dataSource === null) {
            return null;
        }

        $qb = clone $this->dataSource;

        $qb->limit($this->resultLimit)
            ->offset(($this->gridPage * $this->resultLimit));

        return $qb;
    }

    // AJAX REQUEST HANDLERS

    /**
     * Refreshes the grid
     */
    public function actionRefresh(): JsonResponse {
        foreach($this->filters as $name => $filter) {
            if($this->httpRequest->post($name) !== null) {
                $this->activeFilters[$name] = $this->httpRequest->post($name);
            }
        }
        if(!($this instanceof IGridExtendingComponent)) {
            $this->build();
        }
        return new JsonResponse(['grid' => $this->render()]);
    }

    /**
     * Changes the grid page
     */
    public function actionPage(): JsonResponse {
        foreach($this->filters as $name => $filter) {
            if($this->httpRequest->post($name) !== null) {
                $this->activeFilters[$name] = $this->httpRequest->post($name);
            }
        }
        if(!($this instanceof IGridExtendingComponent)) {
            $this->build();
        }
        return new JsonResponse(['grid' => $this->render()]);
    }

    /**
     * Filters the grid data
     */
    public function actionFilter(): JsonResponse {
        foreach($this->filters as $name => $filter) {
            if($this->httpRequest->post($name) !== null) {
                $this->activeFilters[$name] = $this->httpRequest->post($name);
            }
        }

        $this->saveActiveFilters();

        /**
         * When filter is added to a class that extends this class, then it has to call its prerender() method first.
         * After that parent::actionFilter() [this method in this class] can be called.
         * 
         * Before this line was available only for non extending classes but it didn't make sense because it didn't work at all.
         * Build recreates the grid and thus it must be called because otherwise none filters can be applied.
         */
        $this->build();

        return new JsonResponse(['grid' => $this->render()]);
    }

    /**
     * Cleans the grid data filter
     */
    public function actionFilterClear(): JsonResponse {
        if(!($this instanceof IGridExtendingComponent)) {
            $this->build();
        }

        return new JsonResponse(['grid' => $this->render()]);
    }

    /**
     * Exports the limited entries
     */
    public function actionExportLimited(): JsonResponse {
        $ds = clone $this->dataSource;
        $ds = $this->processQueryBuilderDataSource($ds);

        $result = [];
        try {
            $geh = $this->createGridExportHandler($ds);
            [$file, $hash] = $geh->exportNow();
            $result = new JsonResponse(['file' => $file, 'hash' => $hash]);
        } catch(AException $e) {
            throw new GridExportException('Could not process limited export.', $e);
        }

        return $result;
    }

    /**
     * Queues asynchronous unlimited export
     */
    public function actionExportUnlimited(): JsonResponse {
        $ds = clone $this->dataSource;
        $ds = $this->processQueryBuilderDataSource($ds);
        
        $result = [];
        try {
            $geh = $this->createGridExportHandler($ds);
            $hash = $geh->exportAsync();
            $result = new JsonResponse(['hash' => $hash, 'success' => 1]);
        } catch(AException|Exception $e) {
            throw new GridExportException('Could not process unlimited export.', $e);
        }

        return $result;
    }

    /**
     * Handles Quick Search
     */
    public function actionQuickSearch(): JsonResponse {
        if($this->quickSearchQuery === null) {
            $this->quickSearchQuery = $this->httpRequest->post('query');
        }

        //if(!($this instanceof IGridExtendingComponent)) {
            //$this->build();
        //}

        return new JsonResponse(['grid' => $this->render()]);
    }
}

?>