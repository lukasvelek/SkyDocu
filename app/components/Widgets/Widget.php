<?php

namespace App\Components\Widgets;

use App\Core\Http\Ajax\Operations\HTMLPageOperation;
use App\Core\Http\Ajax\Requests\PostAjaxRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\UI\AComponent;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\GridBuilder2\Table;
use App\UI\HTML\HTML;

/**
 * Common widget component
 * 
 * @author Lukas Velek
 */
class Widget extends AComponent {
    private array $data;
    private string $title;
    private bool $hasRefresh;
    private ?array $titleLink;
    private bool $titleHidden;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     */
    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->data = [];
        $this->title = 'Widget';
        $this->hasRefresh = false;
        $this->componentName = 'widget';
        $this->titleLink = null;
        $this->titleHidden = false;
    }

    /**
     * Sets the title link - the address redirected when user clicks on the widget's title
     * 
     * @param array $url Title link
     */
    public function setTitleLink(array $url) {
        $this->titleLink = $url;
    }

    /**
     * Sets the widget internal name
     * 
     * @param string $name Widget internal name
     */
    public function setName(string $name) {
        $this->componentName = $name;
    }

    /**
     * Sets widget data
     * 
     * The array should look like this:
     * ['Elements' => '1444', 'Deleted elements' => '1333'], ...
     * 
     * @param array $data Data array
     */
    public function setData(array $data) {
        $this->data = $data;
    }

    /**
     * Sets the widget title
     * 
     * @param string $title Widget title
     */
    public function setTitle(string $title) {
        $this->title = $title;
    }

    /**
     * Hides the widget title
     */
    public function hideTitle() {
        $this->titleHidden = true;
    }

    /**
     * Shows the widget title
     */
    public function showTitle() {
        $this->titleHidden = false;
    }

    /**
     * Enables data refresh
     * 
     * @param bool $refresh True if refresh button is allowed or false if not
     */
    public function enableRefresh(bool $refresh = true) {
        $this->hasRefresh = $refresh;
    }

    public function render() {
        $template = $this->getTemplate(__DIR__ . '/widget.html');
        $template->widget_title = $this->buildTitle();
        $template->data = $this->build();
        $template->controls = $this->buildControls();
        $template->scripts = $this->buildJSScripts();
        $template->component_name = $this->componentName;

        return $template->render()->getRenderedContent();
    }

    /**
     * Creates HTML code for widget title
     * 
     * @return string HTML code
     */
    private function buildTitle() {
        if($this->titleHidden) {
            return '';
        }

        if($this->titleLink === null) {
            return '<div class="row"><div class="col-md"><p class="widget-title">' . $this->title . '</p></div></div>';
        }

        $el = HTML::el('a')
            ->href($this->convertArrayUrlToStringUrl($this->titleLink))
            ->text($this->title)
            ->class('widget-title');

        return '<div class="row"><div class="col-md">' . $el->toString() . '</div></div>';
    }

    /**
     * Creates HTML code for widget controls
     * 
     * @param bool $isSkeleton Is skeleton
     * @return string HTML code
     */
    private function buildControls(bool $isSkeleton = false) {
        $code = '';

        if($this->hasRefresh) {
            $code .= '<div class="row"><div class="col-md">';
            $code .= $this->createRefreshButton($isSkeleton);
            $code .= '</div></div>';
        }
        
        return $code;
    }

    /**
     * Creates refresh button HTML code
     * 
     * @param bool $isSkeleton Is skeleton
     * @return string HTML code
     */
    private function createRefreshButton(bool $isSkeleton = false) {
        $el = HTML::el('a')
            ->href('#')
            ->text('Refresh &orarr;')
            ->class('link')
        ;

        if($isSkeleton) {
            $el->text('<div id="skeletonTextAnimation" style="width: 10%">Refresh &orarr;</div>');
        } else {
            $el->onClick($this->componentName . '_refresh()');
        }

        return $el->toString();
    }

    /**
     * Creates HTML code for JS scripts
     * 
     * @return string HTML code
     */
    private function buildJSScripts() {
        $scripts = [];

        // GET SKELETON
        $getSkeletonPar = new PostAjaxRequest($this->httpRequest);

        $getSkeletonPar->setComponentUrl($this, 'getSkeleton');

        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId('widget-' . $this->componentName)
            ->setJsonResponseObjectName('widget');

        $getSkeletonPar->addOnFinishOperation($updateOperation);

        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId('widget-' . $this->componentName . '-controls')
            ->setJsonResponseObjectName('controls');

        $getSkeletonPar->addOnFinishOperation($updateOperation);

        $scripts[] = $getSkeletonPar->build();

        // REFRESH CONTROLS
        $par = new PostAjaxRequest($this->httpRequest);

        $par->setComponentUrl($this, 'refresh');

        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId('widget-' . $this->componentName)
            ->setJsonResponseObjectName('widget');

        $par->addOnFinishOperation($updateOperation);

        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId('widget-' . $this->componentName . '-controls')
            ->setJsonResponseObjectName('controls');

        $par->addOnFinishOperation($updateOperation);

        $scripts[] = $par->build();
        $scripts[] = 'async function ' . $this->componentName . '_refresh() {
            await ' . $getSkeletonPar->getFunctionName() . '();
            await sleep(2500);
            await ' . $par->getFunctionName() . '();
        }';

        return '<script type="text/javascript">' . implode(' ', $scripts) . '</script>';
    }

    /**
     * Creates HTML code for widget content
     * 
     * @param bool $isSkeleton Is skeleton
     * @return string HTML code
     */
    protected function build(bool $isSkeleton = false) {
        $rows = $this->processData($isSkeleton);

        $table = new Table($rows);

        return $table->output()->toString();
    }

    /**
     * Processes passed data to table rows
     * 
     * @param bool $isSkeleton Is skeleton
     * @return array<Row> Rows
     */
    private function processData(bool $isSkeleton = false) {
        $rows = [];

        $headerRow = new Row();
        
        $titleCell = new Cell();
        $titleCell->setContent('Title');
        $titleCell->setName('title');
        $titleCell->setHeader();

        $valueCell = new Cell();
        $valueCell->setContent('Value');
        $valueCell->setName('value');
        $valueCell->setHeader();

        $headerRow->addCell($titleCell);
        $headerRow->addCell($valueCell);
        $headerRow->setPrimaryKey(0);

        $rows[] = $headerRow;

        if(empty($this->data)) {
            $cell = new Cell();
            $cell->setContent('No data found.');
            $cell->setName('no-data-found');

            $row = new Row();
            $row->addCell($cell);
            $row->setPrimaryKey(1);

            return [$row];
        }

        $i = 1;
        foreach($this->data as $text => $value) {
            $textCell = new Cell();
            $textCell->setContent('<b>' . $text . '</b>');
            $textCell->setName($i . '-text');

            $valueCell = new Cell();
            $valueCell->setContent($value);
            $textCell->setName($i . '-value');

            if($isSkeleton) {
                $textCell->setContent('<div id="skeletonTextAnimation"><b>' . $text . '</b></div>');
                $valueCell->setContent('<div id="skeletonTextAnimation">' . $value . '</div>');
            }

            $row = new Row();
            $row->addCell($textCell);
            $row->addCell($valueCell);
            $row->setPrimaryKey($i);

            $i++;

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Handles widget refresh control
     * 
     * @return JsonResponse Return value
     */
    public function actionRefresh() {
        return new JsonResponse(['widget' => $this->build(), 'controls' => $this->buildControls()]);
    }

    public function actionGetSkeleton() {
        return new JsonResponse(['widget' => $this->build(true), 'controls' => $this->buildControls(true)]);
    }

    public static function createFromComponent(AComponent $component) {}
}

?>