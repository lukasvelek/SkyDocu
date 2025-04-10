<?php

namespace App\UI\GridBuilder3;

use App\Constants\AConstant;
use App\Constants\IBackgroundColorable;
use App\Constants\IColorable;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\AComponent;
use App\UI\HTML\HTML;

/**
 * GridBuilder3 is the third version of the GridBuilder. This version uses PeeQL as the data source.
 * 
 * @author Lukas Velek
 */
class GridBuilder3 extends AComponent {
    private array $peeQlJson;
    private array $columns;
    private array $labels;

    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->peeQlJson = [];
        $this->columns = [];
        $this->labels = [];
    }

    public static function createFromComponent(AComponent $component) {}

    /**
     * Processes the query and returns the result
     */
    private function processQuery(): array {
        return $this->executePeeQL($this->peeQlJson);
    }

    /**
     * Sets the PeeQL JSON query
     * 
     * @param array $json PeeQL JSON query
     */
    public function setPeeQlJson(array $json) {
        $this->peeQlJson = $json;
    }

    public function render() {
        $result = $this->processQuery();

        $headers = [];
        $values = [];
        foreach($result['data'] as $i => $row) {
            foreach($row as $key => $value) {
                if(array_key_exists($key, $this->columns)) {
                    $label = $this->labels[$key];

                    if(!in_array($label, $headers)) {
                        $headers[] = $label;
                    }

                    $result = $value;
                    foreach($this->columns[$key]->onRenderColumn as $onRender) {
                        try {
                            $result = $onRender($row, $result);
                        } catch(AException $e) {}
                    }

                    $values[$i][$key] = $result;
                }
            }
        }

        $_headers = [];
        foreach($headers as $header) {
            $_headers[] = '<th>' . $header . '</th>';
        }

        $_content = [];
        foreach($values as $i => $row) {
            $tmp = '<tr id="' . $i . '">';

            foreach($row as $key => $value) {
                $tmp .= '<td id="' . $key . '">' . $value . '</td>';
            }

            $tmp .= '</tr>';

            $_content[] = $tmp;
        }

        return '
            <table>
                <thead>
                    <tr>
                        ' . implode('', $_headers) . '
                    </tr>
                </thead>
                <tbody>
                    ' . implode('', $_content) . '
                </tbody>
            </table>
        ';
    }

    /**
     * Adds text column
     * 
     * @param string $name Column name
     * @param ?string $label Column label
     */
    public function addColumnText(string $name, ?string $label = null) {
        $column = $this->addColumn($name);
        $this->labels[$name] = $label ?? $name;

        $column->onRenderColumn[] = function(array $row, mixed $value) {
            return $value;
        };
    }

    /**
     * Adds constant column
     * 
     * @param string $name Column name
     * @param ?string $label Column label
     * @param ?string $constClass Constant class name
     */
    public function addColumnConst(string $name, ?string $label = null, ?string $constClass = null) {
        $column = $this->addColumn($name);
        $this->labels[$name] = $label ?? $name;

        if($constClass !== null) {
            $column->onRenderColumn[] = function(array $row, mixed $value) use ($constClass) {
                if(class_exists($constClass)) {
                    if(in_array(AConstant::class, class_parents($constClass))) {
                        $result = $constClass::toString($value);

                        $el = HTML::el('span');
                        $el->text($result ?? $value);

                        if(in_array(IColorable::class, class_implements($constClass))) {
                            $color = $constClass::getColor($value);

                            $el->style('color', $color);
                        }
                        if(in_array(IBackgroundColorable::class, class_implements($constClass))) {
                            $color = $constClass::getBackgroundColor($value);

                            $el->style('background-color', $color)
                                ->style('border-radius', '10px')
                                ->style('padding', '5px');
                        }

                        return $el->toString();
                    }
                }
            };
        } else {
            $column->onRenderColumn[] = function(array $row, mixed $value) {
                return $value;
            };
        }
    }

    /**
     * Adds column
     * 
     * @param string $name Column name
     */
    private function addColumn(string $name) {
        $column = new Column($name);
        
        $this->columns[$name] = &$column;

        return $column;
    }
}

?>