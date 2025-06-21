<?php

namespace App\UI\GridBuilder2;

use App\Constants\AppDesignThemes;
use App\Core\AjaxRequestBuilder;
use App\Core\Application;
use App\Core\DB\DatabaseRow;
use App\Core\Http\Ajax\Operations\HTMLPageOperation;
use App\Core\Http\Ajax\Requests\AAjaxRequest;
use App\Core\Http\Ajax\Requests\PostAjaxRequest;
use App\Core\Http\HttpRequest;
use App\Helpers\AppThemeHelper;
use App\Helpers\ArrayHelper;
use App\Helpers\DateTimeFormatHelper;
use App\Modules\APresenter;
use App\UI\AComponent;
use App\UI\HTML\HTML;
use Exception;
use QueryBuilder\QueryBuilder;

/**
 * GridBuilderHelper contains useful function for GridBuilder
 * 
 * @author Lukas Velek
 */
class GridBuilderHelper {
    private ?Application $app;
    private ?string $componentName;
    private ?APresenter $presenter;
    private HttpRequest $request;
    
    /**
     * @var array<string, Column> $columns
     */
    public array $columns;
    public array $columnLabels;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest
     */
    public function __construct(HttpRequest $request) {
        $this->request = $request;

        $this->app = null;
        $this->componentName = null;
        $this->presenter = null;
        $this->columns = [];
        $this->columnLabels = [];
    }

    /**
     * Sets the Presenter
     * 
     * @param APresenter $presenter Presenter
     */
    public function setPresenter(APresenter $presenter) {
        $this->presenter = $presenter;
    }

    /**
     * Sets the component name
     * 
     * @param string $componentName Component name
     */
    public function setComponentName(string $componentName) {
        $this->componentName = $componentName;
    }

    /**
     * Sets the Application
     * 
     * @param Application $app Application
     */
    public function setApplication(Application $app) {
        $this->app = $app;
    }

    /**
     * Builds the grid
     *  - creates columns
     *  - creates rows
     *  - creates cells
     *  - creates table
     *  - creates actions
     * 
     * @param array $columns Columns
     * @param array $columnLabels Column labels
     * @param array $onRowRender On Row Render functions
     * @param array $actions Actions
     * @param QueryBuilder $cursor Cursor with data
     * @param string $componentName Grid component name
     * @param string $primaryKeyColName Primary key column name
     * @param bool $actionsDisabled Are actions disabled?
     * @param bool $hasCheckboxes Has checkboxes?
     * @param bool $isSkeleton Is skeleton?
     * @param array $disabledActionList List of disabled actions
     * @return Table Table instance
     */
    public function buildGrid(
        array $onRowRender,
        array $actions,
        QueryBuilder $cursor,
        string $primaryKeyColName,
        bool $actionsDisabled = false,
        bool $hasCheckboxes = false,
        bool $isSkeleton = false,
        array $disabledActionList = []
    ) {
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

        $rowIndex = 0;
        while($row = $cursor->fetchAssoc()) {
            $row = $this->createDatabaseRow($row);
            $rowId = $row->{$primaryKeyColName};

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

                    if($isSkeleton) {
                        $content = '<div id="skeletonTextAnimation">' . $content . '</div>';
                    }

                    $_cell->setContent($content);
                } else {
                    if($content instanceof Cell) {
                        if($isSkeleton) {
                            if($content->content instanceof HTML) {
                                $tmp = HTML::el('div')
                                    ->id('skeletonTextAnimation')
                                    ->text('test');
                                $content->content = $tmp;
                            } else {
                                $content->content = '<div id="skeletonTextAnimation">test</div>';
                            }
                        }
                        $_cell = $content;
                    } else {
                        if($isSkeleton) {
                            if($content instanceof HTML) {
                                $tmp = HTML::el('div')
                                    ->id('skeletonTextAnimation')
                                    ->text('test');
                                $content = $tmp;
                            } else {
                                $content = '<div id="skeletonTextAnimation">test</div>';
                            }
                        }
                        $_cell->setContent($content);
                    }
                }

                $_row->addCell($_cell);
            }

            if(!empty($onRowRender)) {
                foreach($onRowRender as $render) {
                    try {
                        $render($row, $_row, $_row->html);
                    } catch(Exception $e) {}
                }
            }

            if($hasCheckboxes) {
                $rowCheckbox = new RowCheckbox($rowId, $this->componentName . '_onCheckboxCheck(\'' . $rowId . '\')');
                $rowCheckbox->setSkeleton($isSkeleton);
                $_row->addCell($rowCheckbox, true);
            }

            $_tableRows[] = $_row;
            $rowIndex++;
        }

        if($hasCheckboxes && count($_tableRows) > 1) {
            $_headerCell = new Cell();
            $_headerCell->setName('checkboxes');
            $_headerCell->setHeader();
            $_headerCell->setContent('');
            $_tableRows['header']->addCell($_headerCell, true);
        }

        if(!empty($actions) && !$actionsDisabled && (count($actions) > count($disabledActionList))) {
            $maxCountToRender = 0;
            $canRender = [];
            $actionRenderCount = [];

            foreach($_tableRows as $k => $_row) {
                if($k == 'header') continue;

                $i = 0;
                foreach($actions as $actionName => $action) {
                    $cAction = clone $action;

                    if(in_array($actionName, $disabledActionList)) continue;

                    foreach($cAction->onCanRender as $render) {
                        try {
                            $result = $render($_row->rowData, $_row, $cAction);

                            if($result == true) {
                                $canRender[$k][$actionName] = $cAction;
                                $i++;

                                if(array_key_exists($actionName, $actionRenderCount)) {
                                    $actionRenderCount[$actionName]++;
                                } else {
                                    $actionRenderCount[$actionName] = 1;
                                }
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
            if(count($actions) == $maxCountToRender) {
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

                        if($isSkeleton) {
                            $_cell->setContent('<div id="skeletonTextAnimation">test</div>');
                        }

                        $cells[$k][$actionName] = $_cell;
                    }
                }
            } else {
                $displayedActions = [];
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

                            if($isSkeleton) {
                                $_cell->setContent('<div id="skeletonTextAnimation">test</div>');
                            }

                            $cells[$k][$actionName] = $_cell;
                            $displayedActions[] = $actionName;
                        }
                    }

                    if(!array_key_exists($k, $cells)) {
                        foreach($actionData as $actionName => $action) {
                            if(!in_array($actionName, $displayedActions) && !array_key_exists($actionName, $actionRenderCount)) continue;

                            $_cell = new Cell();
                            $_cell->setName($actionName);
                            $_cell->setContent('');
                            $_cell->setClass('grid-cell-action');

                            if($isSkeleton) {
                                $_cell->setContent('<div id="skeletonTextAnimation">test</div>');
                            }

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
            $content = 'No data found';
            if($isSkeleton) {
                $content = '<div id="skeletonTextAnimation">' . $content . '</div>';
            }

            $_cell = new Cell();
            $_cell->setName('NoDataCell');
            $_cell->setContent($content);
            $_cell->setSpan(count($this->columns));
            
            $_row = new Row();
            $_row->addCell($_cell);

            $_tableRows['nodatacell'] = $_row;
        }

        return new Table($_tableRows);
    }

    /**
     * Creates a DatabaseRow instance from $row
     * 
     * @param mixed $row mysqli_result
     * @return DatabaseRow DatabaseRow instance
     */
    private function createDatabaseRow(mixed $row) {
        $r = new DatabaseRow();

        foreach($row as $k => $v) {
            $r->$k = $v;
        }

        return $r;
    }

    /**
     * Adds a column to the grid
     * 
     * @param string $name Column name
     * @param string $type Column type
     * @param ?string $label column label
     * @return Column Column instance
     */
    public function addColumn(
        string $name,
        string $type,
        ?string $label = null
    ) {
        $col = new Column($name);
        $this->columns[$name] = &$col;
        if($label !== null) {
            $this->columnLabels[$name] = $label;
        } else {
            $this->columnLabels[$name] = $name;
        }

        if($type == GridColumnTypes::COL_TYPE_DATETIME) {
            $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
                if($value === null) {
                    return '-';
                }

                $html->title(DateTimeFormatHelper::formatDateToUserFriendly($value, DateTimeFormatHelper::ATOM_FORMAT));
                return DateTimeFormatHelper::formatDateToUserFriendly($value, substr($this->app->currentUser->getDatetimeFormat(), 0, -2));
            };

            $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) {
                return DateTimeFormatHelper::formatDateToUserFriendly($value);
            };
        } else if($type == GridColumnTypes::COL_TYPE_USER) {
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
        } else if($type == GridColumnTypes::COL_TYPE_BOOLEAN) {
            $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
                if($value === true || $value == 1) {
                    $color = 'green';
                    $bgColor = 'lightgreen';

                    $el = HTML::el('span')
                            ->style('border-radius', '12px')
                            ->style('padding', '5px')
                            ->text('&check;');

                    if($this->app !== null &&
                        $this->app->currentUser !== null &&
                        $this->app->currentUser->getAppDesignTheme() == AppDesignThemes::DARK
                    ) {
                        $el->style('color', $bgColor)
                            ->style('background-color', 'dark' . $color);
                    } else {
                        $el->style('color', $color)
                            ->style('background-color', $bgColor);
                    }

                    $cell->setContent($el);
                } else {
                    $color = 'red';
                    $bgColor = 'pink';

                    $el = HTML::el('span')
                            ->style('border-radius', '12px')
                            ->style('padding', '5px')
                            ->text('&times;');

                    if($this->app !== null &&
                        $this->app->currentUser !== null &&
                        $this->app->currentUser->getAppDesignTheme() == AppDesignThemes::DARK
                    ) {
                        $el->style('color', $bgColor)
                            ->style('background-color', 'dark' . $color);
                    } else {
                        $el->style('color', $color)
                            ->style('background-color', $bgColor);
                    }
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
     * Creates necessary JS scripts
     * 
     * @return string HTML code
     */
    public function createScripts(
        array $filters,
        array $activeFilters,
        array $queryDependencies,
        array $quickSearchFilter,
        AComponent $component,
        string $gridName,
        bool $enableExport,
        bool $hasCheckboxes,
        array $checkboxHandler
    ) {
        $scripts = [];

        $addScript = function(AjaxRequestBuilder|AAjaxRequest $arb) use (&$scripts) {
            if($arb instanceof AjaxRequestBuilder) {
                $scripts[] = $arb->build();
            } else if($arb instanceof AAjaxRequest) {
                $scripts[] = $arb->build();
            }
        };

        $data = [
            'gridPage' => '_page'
        ];

        $args = [
            '_page'
        ];

        if(!empty($activeFilters)) {
            foreach($activeFilters as $name => $value) {
                $argName = '_' . $name;

                $data[$name] = $argName;
                $args[] = $argName;
            }
        }

        if(!empty($queryDependencies)) {
            foreach($queryDependencies as $name => $value) {
                $argName = '_' . $name;

                $data[$name] = $argName;
                $args[] = $argName;
            }
        }

        // GET SKELETON
        $getSkeletonPar = new PostAjaxRequest($this->request);

        $getSkeletonPar->setComponentUrl($component, 'getSkeleton')
            ->setData($data);

        foreach($args as $arg) {
            $getSkeletonPar->addArgument($arg);
        }

        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId('grid-' . $gridName . '-controls')
            ->setJsonResponseObjectName('controls');

        $getSkeletonPar->addOnFinishOperation($updateOperation);

        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId('grid-' . $gridName)
            ->setJsonResponseObjectName('grid');

        $getSkeletonPar->addOnFinishOperation($updateOperation);

        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId('grid-' . $gridName . '-filters')
            ->setJsonResponseObjectName('filters');

        $getSkeletonPar->addOnFinishOperation($updateOperation);

        $addScript($getSkeletonPar);

        // REFRESH
        $par = new PostAjaxRequest($this->request);

        $par->setComponentUrl($component, 'refresh')
            ->setData($data);

        foreach($args as $arg) {
            $par->addArgument($arg);
        }

        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId($gridName)
            ->setJsonResponseObjectName('grid');

        $par->addOnFinishOperation($updateOperation);

        $addScript($par);
        $scripts[] = '
            async function ' . $this->componentName . '_gridRefresh(' . implode(', ', $args) . ') {
                await ' . $getSkeletonPar->getFunctionName() . '(' . implode(', ', $args) . ');
                await sleep(1000);
                await ' . $par->getFunctionName() . '(' . implode(', ', $args) . ');
            }
        ';

        // PAGINATION
        $par = new PostAjaxRequest($this->request);

        $data = [
            'gridPage' => '_page'
        ];

        $args = [
            '_page'
        ];

        if(!empty($activeFilters)) {
            foreach($activeFilters as $name => $value) {
                $argName = '_' . $name;

                $data[$name] = $argName;
                $args[] = $argName;
            }
        }

        if(!empty($queryDependencies)) {
            foreach($queryDependencies as $name => $value) {
                $argName = '_' . $name;

                $data[$name] = $argName;
                $args[] = $argName;
            }
        }

        $par->setComponentUrl($component, 'page')
            ->setData($data);

        foreach($args as $arg) {
            $par->addArgument($arg);
        }

        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId($gridName)
            ->setJsonResponseObjectName('grid');

        $par->addOnFinishOperation($updateOperation);

        $addScript($par);
        $scripts[] = '
            function ' . $this->componentName . '_page(' . implode(', ', $args) . ') {
                ' . $par->getFunctionName() . '(' . implode(', ', $args) . ');
            }
        ';

        // FILTER
        if(!empty($filters)) {
            $par = new PostAjaxRequest($this->request);

            $data = [];
            $args = [];

            foreach($filters as $name => $value) {
                $argName = '_' . $name;

                $data[$name] = $argName;
                $args[] = $argName;
            }
    
            if(!empty($queryDependencies)) {
                foreach($queryDependencies as $name => $value) {
                    $argName = '_' . $name;
    
                    $data[$name] = $argName;
                    $args[] = $argName;
                }
            }

            $par->setComponentUrl($component, 'filter')
                ->setData($data);

            foreach($args as $arg) {
                $par->addArgument($arg);
            }

            $updateOperation = new HTMLPageOperation();
            $updateOperation->setHtmlEntityId('grid')
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
        if(!empty($filters)) {
            $scripts[] = '
                    async function ' . $this->componentName . '_processFilterModalOpen() {
                        $("#grid-filter-modal-inner")
                            .css("height", "90%")
                            .css("visibility", "visible")
                            .css("width", "90%");
                    }
            ';
        }

        if(!empty($filters) || !empty($quickSearchFilter)) {
            $par = new PostAjaxRequest($this->request);

            $data = [];
            $args = [];
    
            if(!empty($queryDependencies)) {
                foreach($queryDependencies as $name => $value) {
                    $argName = '_' . $name;
    
                    $data[$name] = $argName;
                    $args[] = $argName;
                }
            }

            $par->setData($data)
                ->setComponentUrl($component, 'filterClear');

            foreach($args as $arg) {
                $par->addArgument($arg);
            }

            $updateOperation = new HTMLPageOperation();
            $updateOperation->setHtmlEntityId('grid')
                ->setJsonResponseObjectName('grid');

            $par->addOnFinishOperation($updateOperation);

            $addScript($par);
            $scripts[] = '
                async function ' . $this->componentName . '_filterClear(' . implode(', ', $args) . ') {
                    await ' . $par->getFunctionName() . '(' . implode(', ', $args) . ');
                }
            ';
        }

        // QUICK SEARCH
        if(!empty($quickSearchFilter)) {
            $par = new PostAjaxRequest($this->request);

            $data['query'] = '_query';

            $par->setComponentUrl($component, 'quickSearch')
                ->setData($data);

            foreach($args as $arg) {
                $par->addArgument($arg);
            }
            $par->addArgument('_query');

            $op = new HTMLPageOperation();
            $op->setHtmlEntityId($gridName)
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
        if($enableExport) {
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
        if($enableExport) {
            $arb = new AjaxRequestBuilder();

            $headerParams = [];
            $fArgs = [];
            foreach($filters as $name => $filter) {
                $hName = '_' . $name;
                $headerParams[$name] = $hName;
                $fArgs[] = $hName;
            }

            if(!empty($queryDependencies)) {
                foreach($queryDependencies as $k => $v) {
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
        if($hasCheckboxes) {
            $arb = new AjaxRequestBuilder();

            $headerParams = [
                'ids[]' => '_ids'
            ];

            if(array_key_exists('params', $checkboxHandler)) {
                foreach($checkboxHandler['params'] as $paramName => $paramKey) {
                    $headerParams[$paramName] = $paramKey;
                }
            }

            $arb->setComponentAction($checkboxHandler['presenter'], $this->componentName . '-' . $checkboxHandler['action']);

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

            if(AppThemeHelper::getAppThemeForUser($this->app) == AppDesignThemes::DARK) {
                $modalStyle = 'visibility: hidden; height: 0px; position: absolute; top: 5%; left: 5%; background-color: rgba(70, 70, 70, 1); z-index: 9999; border-radius: 5px;';
            } else {
                $modalStyle = 'visibility: hidden; height: 0px; position: absolute; top: 5%; left: 5%; background-color: rgba(225, 225, 225, 1); z-index: 9999; border-radius: 5px;';
            }

            $scripts[] = '
                    function ' . $this->componentName . '_processBulkActionsModalOpen(_showLoading) {
                        if(_showLoading) {
                            $("#modal").html(\'<div id="bulk-actions-modal-inner" style="' . $modalStyle . '"></div>\');
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
}

?>