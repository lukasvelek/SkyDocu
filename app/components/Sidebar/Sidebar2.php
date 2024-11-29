<?php

namespace App\Components\Sidebar;

use App\Core\Http\HttpRequest;
use App\Modules\TemplateObject;
use App\UI\AComponent;

class Sidebar2 extends AComponent {
    protected array $links;
    private TemplateObject $template;

    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->links = [];
        $this->template = new TemplateObject(file_get_contents(__DIR__ . '\\template.html'));
    }

    /**
     * Adds horizontal line
     */
    public function addHorizontalLine() {
        $this->links[] = '<hr>';
    }

    /**
     * Adds link
     * 
     * @param string $title Link title
     * @param array $url Link URL
     * @param bool $isActive Is link active?
     */
    public function addLink(string $title, array $url, bool $isActive = false) {
        $this->links[] = $this->createLink($title, $url, $isActive);
    }

    /**
     * Adds JS link
     * 
     * @param string $title Link title
     * @param string $jsHandler JS handler
     * @param bool $isActive Is link active?
     */
    public function addJSLink(string $title, string $jsHandler, bool $isActive = false) {
        if($isActive) {
            $title = '<b>' . $title . '</b>';
        }

        $code = '<a class="sidebar-link" href="#" onclick="' . $jsHandler . '">' . $title . '</a>';

        $this->links[] = $code;
    }

    /**
     * Composes URL to single line
     * 
     * @param array $urlParts URL parts
     * @return string Single line URL
     */
    private function composeURL(array $urlParts) {
        $url = '?';

        $urlCouples = [];
        foreach($urlParts as $upKey => $upValue) {
            $urlCouples[] = $upKey . '=' . $upValue;
        }

        $url .= implode('&', $urlCouples);

        return $url;
    }

    /**
     * Creates a link line
     * 
     * @param string $title Link title
     * @param array $url Link URL
     * @param bool $isActive Is link active?
     * @return string HTML code
     */
    protected function createLink(string $title, array $url, bool $isActive) {
        $url = $this->composeURL($url);

        if($isActive) {
            $title = '<b>' . $title . '</b>';
        }

        $code = '<a class="sidebar-link" href="' . $url . '">' . $title . '</a>';
        return $code;
    }

    /**
     * Renders the content to HTML code
     * 
     * @return string HTML code
     */
    public function render() {
        $linkCode = '';
        foreach($this->links as $link) {
            if($link == '<hr>') {
                $linkCode .= $link;
            } else {
                $linkCode .= $link . '<br>';
            }
        }
        $this->template->links = $linkCode;

        return $this->template->render()->getRenderedContent();
    }

    public static function createFromComponent(AComponent $component) {}
}

?>