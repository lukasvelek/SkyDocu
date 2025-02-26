<?php

namespace App\UI\ListBuilder;

use App\Constants\AConstant;
use App\Constants\IBackgroundColorable;
use App\Constants\IColorable;
use App\Exceptions\GeneralException;
use App\Helpers\DateTimeFormatHelper;
use App\UI\HTML\HTML;
use Exception;

class ListBuilderHelper {
    private array $columns;
    private array $columnLabels;
    private array $actions;
    private array $dataSource;

    public function __construct() {
        $this->columns = [];
        $this->columnLabels = [];
        $this->actions = [];
        $this->dataSource = [];
    }

    /**
     * Adds column to the list
     * 
     * @param string $name Column name
     * @param ?string $label Column label
     * @param string $type Column type
     * @param ?string $constClass Constant class name
     */
    public function addColumn(string $name, ?string $label, string $type, ?string $constClass = null) {
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

    public function render() {
        if(empty($this->dataSource)) {
            throw new GeneralException('Data source is empty.');
        }

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
            $_row = new ListRow();
            $_row->index = ($index + 1);
            $_row->rowData = ArrayRow::createFromArrayData($data);
        }
    }
}

?>