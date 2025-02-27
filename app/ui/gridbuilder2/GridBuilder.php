<?php

namespace App\UI\GridBuilder2;

use App\Constants\AConstant;
use App\Constants\IBackgroundColorable;
use App\Constants\IColorable;
use App\Core\Caching\CacheFactory;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\GridExportException;
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
    protected ?QueryBuilder $dataSource;
    protected ?QueryBuilder $fullDataSource;
    protected string $primaryKeyColName;
    private ?Table $table;
    private bool $enablePagination;
    private bool $enableExport;
    private int $gridPage;
    private ?int $totalCount;
    private GridHelper $gridHelper;
    protected string $gridName;
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

    private GridBuilderHelper $helper;
    private ?string $containerId;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     */
    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->dataSource = null;
        $this->fullDataSource = null;
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
        $this->containerId = null;

        $this->helper = new GridBuilderHelper($request);
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

        $this->helper->setApplication($this->app);
        $this->helper->setComponentName($this->componentName);
        $this->helper->setPresenter($this->presenter);
        $this->gridPage = $this->getGridPage();
    }

    /**
     * Sets container ID
     * 
     * @param ?string $containerId Container ID
     */
    public function setContainerId(?string $containerId) {
        $this->containerId = $containerId;
    }

    /**
     * Returns the name of the grid filter cache namespace
     * 
     * @return string Cache namespace
     */
    private function getCacheNameForFilter(): string {
        return CacheNames::GRID_FILTER_DATA . '\\' . $this->gridName . '\\' . $this->app->currentUser?->getId() . '\\' . $this->containerId;
    }

    /**
     * Retrieves active filters from cache
     */
    protected function getActiveFiltersFromCache() {
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
        
        $activeFilters = $this->activeFilters;

        $cache->save($this->gridName . $this->app->currentUser?->getId(), function() use ($activeFilters) {
            return $activeFilters;
        });
    }

    /**
     * Clears active filters (in cache)
     */
    protected function clearActiveFilters() {
        $this->cacheFactory->invalidateCacheByNamespace($this->getCacheNameForFilter());

        $this->activeFilters = [];

        $this->saveActiveFilters();
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
        $col = $this->addColumn($name, GridColumnTypes::COL_TYPE_TEXT, $label);
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($constClass) {
            $result = null;
            try {
                if(class_exists($constClass)) {
                    if(in_array(AConstant::class, class_parents($constClass))) {
                        $result = $constClass::toString($value);

                        $el = HTML::el('span');
                        $el->text($result ?? '-');

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
        return $this->addColumn($name, GridColumnTypes::COL_TYPE_USER, $label);
    }

    /**
     * Adds a column representing a boolean value
     * 
     * @param string $name Column name
     * @param ?string $label column label
     * @return Column Column instance
     */
    public function addColumnBoolean(string $name, ?string $label = null) {
        return $this->addColumn($name, GridColumnTypes::COL_TYPE_BOOLEAN, $label);
    }

    /**
     * Adds a column representing a datetime
     * 
     * @param string $name Column name
     * @param ?string $label column label
     * @return Column Column instance
     */
    public function addColumnDatetime(string $name, ?string $label = null) {
        return $this->addColumn($name, GridColumnTypes::COL_TYPE_DATETIME, $label);
    }

    /**
     * Adds a general column
     * 
     * @param string $name Column name
     * @param ?string $label Column label
     * @return Column Column instance
     */
    public function addColumnText(string $name, ?string $label = null) {
        return $this->addColumn($name, GridColumnTypes::COL_TYPE_TEXT, $label);
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
        return $this->helper->addColumn($name, $type, $label);
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
        if(empty($this->activeFilters)) {
            $this->getActiveFiltersFromCache();
        }
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
     * 
     * @param bool $isSkeleton Is skeleton
     * @param bool $explicit Explicit fetch from DB
     */
    private function build(bool $isSkeleton = false, bool $explicit = false) {
        $data = $this->fetchDataFromDb($explicit);

        $this->table = $this->helper->buildGrid(
            $this->onRowRender,
            $this->actions,
            $data,
            $this->primaryKeyColName,
            $this->actionsDisabled,
            $this->hasCheckboxes,
            $isSkeleton
        );
    }

    /**
     * Creates grid controls
     * 
     * @param bool $isSkeleton Is skeleton
     * @return string HTML code
     */
    private function createGridControls(bool $isSkeleton = false) {
        $code = '
            <div class="row">
                <div class="col-md">
                    ' . $this->createGridPagingControl($isSkeleton) . '
                </div>
                
                <div class="col-md">
                    ' . $this->createGridPageInfo($isSkeleton) . '
                </div>
                
                <div class="col-md" ' . ($this->enableExport ? '' : ' id="right"') . '>
                    ' . $this->createGridRefreshControl($isSkeleton) . '
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
        return $this->helper->createScripts(
            $this->filters,
            $this->activeFilters,
            $this->queryDependencies,
            $this->quickSearchFilter,
            $this,
            $this->gridName,
            $this->enableExport,
            $this->hasCheckboxes,
            $this->checkboxHandler
        );
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
     * @param bool $isSkeleton Is skeleton
     * @return string Paging information
     */
    private function createGridPageInfo(bool $isSkeleton = false) {
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

        $text = 'Page ' . $displayGridPage . ' of ' . $lastPage . ' (' . ($this->resultLimit * $this->gridPage) . ' - ' . $lastPageCount . ')';

        if($isSkeleton) {
            $text = '<div id="skeletonTextAnimation">' . $text . '</div>';
        }

        return $text;
    }

    /**
     * Creates grid refresh control
     * 
     * @param bool $isSkeleton Is skeleton
     * @return string HTML code
     */
    private function createGridRefreshControl(bool $isSkeleton = false) {
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

        if($isSkeleton) {
            return '<a class="link" href="#"><div id="skeletonTextAnimation">Refresh &orarr;</div></a>';
        } else {
            return '<a class="link" href="#" onclick="' . $this->componentName . '_gridRefresh(' . implode(', ', $args) . ')" title="Refresh grid">Refresh &orarr;</a>';
        }
    }

    /**
     * Creates grid paging control
     * 
     * @param bool $isSkeleton Is skeleton
     * @return string HTML code
     */
    private function createGridPagingControl(bool $isSkeleton = false) {
        if(!$this->enablePagination) {
            return '';
        }

        $totalCount = $this->getTotalCount();
        $lastPage = (int)ceil($totalCount / $this->resultLimit) - 1;

        $firstPageBtn = $this->createPagingButtonCode(0, '&lt;&lt;', ($this->gridPage == 0));
        $previousPageBtn = $this->createPagingButtonCode(($this->gridPage - 1), '&lt;', ($this->gridPage == 0));
        $nextPageBtn = $this->createPagingButtonCode(($this->gridPage + 1), '&gt;', ($this->gridPage >= $lastPage));
        $lastPageBtn = $this->createPagingButtonCode($lastPage, '&gt;&gt;', ($this->gridPage >= $lastPage));

        $text = implode('', [$firstPageBtn, $previousPageBtn, $nextPageBtn, $lastPageBtn]);

        if($isSkeleton) {
            $text = '<div id="skeletonTextAnimation">paging buttons</div>';
        }

        return $text;
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

        if($this->httpRequest->get('gridPage') !== null) {
            $page = $this->httpRequest->get('gridPage');
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
     * @param bool $isSkeleton Is skeleton
     * @return string HTML code
     */
    private function createGridFilterControls(bool $isSkeleton = false) {
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

        if($isSkeleton) {
            $el->text('<div id="skeletonTextAnimation" style="width: 35%">placeholder</div>');
        } else {
            $el->text(implode('&nbsp;', $btns));
        }

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
        $filter->setGridColumns($this->helper->columnLabels);
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
            $this->helper->columns,
            $this->helper->columnLabels,
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
            'action' => $componentAction
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
            if($this->httpRequest->get($name) !== null) {
                $this->activeFilters[$name] = $this->httpRequest->get($name);
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
        $this->build(false, true);

        return new JsonResponse(['grid' => $this->render()]);
    }

    /**
     * Cleans the grid data filter
     */
    public function actionFilterClear(): JsonResponse {
        $this->clearActiveFilters();

        $this->build();

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

        return new JsonResponse(['grid' => $this->render()]);
    }

    /**
     * Handles Get skeleton
     */
    public function actionGetSkeleton(): JsonResponse {
        $this->build(true);

        return new JsonResponse([
            'grid' => $this->table->output()->toString(),
            'controls' => $this->createGridControls(true),
            'filters' => $this->createGridFilterControls(true)
        ]);
    }
}

?>