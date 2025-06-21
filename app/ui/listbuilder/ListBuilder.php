<?php

namespace App\UI\ListBuilder;

use App\Core\Http\HttpRequest;
use App\UI\AComponent;

/**
 * ListBuilder allows building lists
 * 
 * @author Lukas Velek
 */
class ListBuilder extends AComponent {
    private array $dataSource;
    private string $listName;
    private ListBuilderHelper $helper;
    private ListTable $table;

    /**
     * Class constructor
     */
    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->helper = new ListBuilderHelper();

        $this->dataSource = [];
        $this->listName = 'MyList';
    }

    /**
     * Sets list name
     * 
     * @param string $listName
     */
    public function setListName(string $listName) {
        $this->listName = $listName;
    }

    /**
     * Sets data source for the list
     * 
     * @param array $dataSource
     */
    public function setDataSource(array $dataSource) {
        $this->dataSource = $dataSource;
    }

    /**
     * Adds general column
     * 
     * @param string $name Column name
     * @param ?string $label Column label
     */
    public function addColumnText(string $name, ?string $label = null): ListColumn {
        return $this->helper->addColumn($name, $label, ListColumnTypes::COL_TYPE_TEXT);
    }

    /**
     * Adds boolean column
     * 
     * @param string $name Column name
     * @param ?string $label Column label
     */
    public function addColumnBoolean(string $name, ?string $label = null): ListColumn {
        return $this->helper->addColumn($name, $label, ListColumnTypes::COL_TYPE_BOOLEAN);
    }

    /**
     * Adds datetime column
     * 
     * @param string $name Column name
     * @param ?string $label Column label
     */
    public function addColumnDatetime(string $name, ?string $label = null): ListColumn {
        return $this->helper->addColumn($name, $label, ListColumnTypes::COL_TYPE_DATETIME);
    }

    /**
     * Adds constant column
     * 
     * @param string $name Column name
     * @param ?string $label Column label
     */
    public function addColumnConst(string $name, ?string $label = null, string $constClass = ''): ListColumn{
        return $this->helper->addColumn($name, $label, ListColumnTypes::COL_TYPE_CONST, $constClass);
    }

    /**
     * Adds action
     * 
     * @param string $action Action name
     */
    public function addAction(string $name): ListAction {
        return $this->helper->addAction($name);
    }

    public function startup() {
        parent::startup();

        $this->helper->setApplication($this->app);
    }

    public function prerender() {
        parent::prerender();

        $this->helper->setDataSource($this->dataSource);
    }

    public function render() {
        $this->table = $this->helper->render();

        $template = $this->getTemplate(__DIR__ . '/list.html');

        $template->list = $this->table->output();
        $template->list_name = $this->listName;

        return $template->render()->getRenderedContent();
    }

    public static function createFromComponent(AComponent $component) {}
}

?>