<?php

namespace App\UI\ListBuilder;

use App\Constants\AConstant;
use App\Constants\IBackgroundColorable;
use App\Constants\IColorable;
use App\Helpers\ArrayHelper;
use App\Helpers\DateTimeFormatHelper;
use App\UI\HTML\HTML;
use Exception;

class ListBuilderHelper {
    /**
     * @var array<string, ListColumn> $columns
     */
    private array $columns;
    private array $columnLabels;
    /**
     * @var array<string, ListAction> $actions
     */
    private array $actions;
    private array $dataSource;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->columns = [];
        $this->columnLabels = [];
        $this->actions = [];
        $this->dataSource = [];
    }

    /**
     * Adds action to the list
     * 
     * @param string $name Action name
     */
    public function addAction(string $name): ListAction {
        $action = new ListAction($name);
        $this->actions[$name] = &$action;
        return $action;
    }

    /**
     * Adds column to the list
     * 
     * @param string $name Column name
     * @param ?string $label Column label
     * @param string $type Column type
     * @param ?string $constClass Constant class name
     */
    public function addColumn(string $name, ?string $label, string $type, ?string $constClass = null): ListColumn {
        $column = new ListColumn($name);
        $this->columns[$name] = &$column;
        $this->columnLabels[$name] = $label ?? $name;

        switch($type) {
            case ListColumnTypes::COL_TYPE_BOOLEAN:
                $column->onRenderColumn[] = function(ArrayRow $row, ListRow $_row, ListCell $cell, mixed $value) {
                    if($value == true) {
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
                break;

            case ListColumnTypes::COL_TYPE_CONST:
                $column->onRenderColumn[] = function(ArrayRow $row, ListRow $_row, ListCell $cell, mixed $value) use ($constClass) {
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
                break;

            case ListColumnTypes::COL_TYPE_DATETIME:
                $column->onRenderColumn[] = function(ArrayRow $row, ListRow $_row, ListCell $cell, mixed $value) {
                    if($value === null) {
                        return '-';
                    }

                    $el = HTML::el('span')
                        ->title(DateTimeFormatHelper::formatDateToUserFriendly($value, DateTimeFormatHelper::ATOM_FORMAT))
                        ->text(DateTimeFormatHelper::formatDateToUserFriendly($value));

                    return $el;
                };
                break;
        }

        return $column;
    }

    /**
     * Sets data source for list
     * 
     * @param array $dataSource Data source
     */
    public function setDataSource(array $dataSource) {
        $this->dataSource = $dataSource;
    }

    /**
     * Renders the list
     */
    public function render(): ListTable {
        $_tableRows = [];

        $_headerRow = new ListRow();
        foreach($this->columns as $name => $entity) {
            $_headerCell = new ListCell();
            $_headerCell->setName($name);
            $_headerCell->setContent($this->columnLabels[$name]);
            $_headerCell->setHeader();
            $_headerRow->addCell($_headerCell);
        }

        $_tableRows['header'] = $_headerRow;

        foreach($this->dataSource as $index => $data) {
            $row = ArrayRow::createFromArrayData($data);
            $_row = new ListRow();
            $_row->index = ($index + 1);
            $_row->rowData = $row;
            
            foreach($this->columns as $name => $column) {
                $_cell = new ListCell();
                $_cell->setName($name);

                if(in_array($name, $row->getKeys())) {
                    $content = $row->$name;

                    if(!empty($this->columns[$name]->onRenderColumn)) {
                        foreach($this->columns[$name]->onRenderColumn as $render) {
                            try {
                                $content = $render($row, $_row, $_cell, $content);
                            } catch(Exception $e) {}
                        }
                    }
                } else {
                    $content = '-';

                    if(!empty($this->columns[$name]->onRenderColumn)) {
                        foreach($this->columns[$name]->onRenderColumn as $render) {
                            try {
                                $content = $render($row, $_row, $_cell, $content);
                            } catch(Exception $e) {}
                        }
                    }
                }

                if($content === null) {
                    $content = '-';
                    $_cell->setContent($content);
                } else {
                    if($content instanceof ListCell) {
                        $_cell = $content;
                    } else {
                        $_cell->setContent($content);
                    }
                }

                $_row->addCell($_cell);
            }

            $_tableRows['col-' . $index] = $_row;
        }

        if(!empty($this->actions)) {
            $maxCountToRender = 0;
            $canRender = [];

            foreach($_tableRows as $k => $_row) {
                if($k == 'header') continue;

                $i = 0;
                foreach($this->actions as $actionName => $action) {
                    $_action = clone $action;

                    foreach($_action->onCanRender as $render) {
                        try {
                            $result = $render($row, $_row, $_action);

                            if($result == true) {
                                $canRender[$k][$actionName] = $_action;
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
                        if($action instanceof ListAction) {
                            $_action = clone $action;
                            $_action->inject($row, $_row, $k);
                            $_cell = new ListCell();
                            $_cell->setName($actionName);
                            $_cell->setContent($_action->output()->toString());
                            $_cell->setClass('grid-cell-action');
                        } else {
                            $_cell = new ListCell();
                            $_cell->setName($actionName);
                            $_cell->setContent('');
                            $_cell->setClass('grid-cell-action');
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
                        if($action instanceof ListAction) {
                            $_action = clone $action;
                            $_action->inject($row, $_row, $k);
                            $_cell = new ListCell();
                            $_cell->setName($actionName);
                            $_cell->setContent($_action->output()->toString());
                            $_cell->setClass('grid-cell-action');

                            $cells[$k][$actionName] = $_cell;
                            $displayedActions[] = $actionName;
                        }
                    }

                    if(!array_key_exists($k, $cells)) {
                        foreach($actionData as $actionName => $action) {
                            if(!in_array($actionName, $displayedActions)) continue;

                            $_cell = new ListCell();
                            $_cell->setName($actionName);
                            $_cell->setContent('');
                            $_cell->setClass('grid-cell-action');

                            $cells[$k][$actionName] = $_cell;
                        }
                    }
                }
            }

            $tmp = [];
            foreach($cells as $k => $c) {
                foreach($c as $cell) {
                    if(!in_array($cell->getName(), $tmp)) {
                        $tmp[] = $cell->getName();
                    }
                }
            }

            if(count($tmp) > 0) {
                $_headerCell = new ListCell();
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
                        $_cell = new ListCell();
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

            $_cell = new ListCell();
            $_cell->setName('NoDataCell');
            $_cell->setContent($content);
            $_cell->setSpan(count($this->columns));

            $_row = new ListRow();
            $_row->addCell($_cell);

            $_tableRows['nodatacell'] = $_row;
        }

        return new ListTable($_tableRows);
    }
}

?>