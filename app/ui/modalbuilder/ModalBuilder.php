<?php

namespace App\UI\ModalBuilder;

use App\Core\Http\HttpRequest;
use App\Modules\TemplateObject;
use App\UI\AComponent;
use App\UI\FormBuilder2\FormBuilder2;

/**
 * ModalBuilder allows creating modals
 * 
 * @author Lukas Velek
 */
class ModalBuilder extends AComponent {
    private string $id;
    protected string $title;
    protected string $content;
    protected array $scripts;
    protected ?string $templateFile;
    protected ?TemplateObject $template;

    /**
     * Class constructor
     * 
     * @param HttpRequest $httpRequest HttpRequest instance
     */
    public function __construct(HttpRequest $httpRequest) {
        parent::__construct($httpRequest);

        $this->id = 'modal-inner';
        $this->title = 'Modal';
        $this->content = 'Modal content';
        $this->scripts = [];
        $this->templateFile = null;
        $this->template = null;
    }

    /**
     * Sets the modal ID
     * 
     * @param string $id Modal ID
     */
    public function setId(string $id) {
        $this->id = $id;
    }

    /**
     * Sets the modal title
     * 
     * @param string $title Modal title
     */
    public function setTitle(string $title) {
        $this->title = $title;
    }

    /**
     * Sets content from FormBuilder
     * 
     * @param FormBuilder2 $form FormBuilder instance
     */
    public function setContentFromFormBuilder(FormBuilder2 $form) {
        $this->content = $form->render();
    }

    public function startup() {
        parent::startup();

        $templateLink = $this->templateFile ?? __DIR__ . '/modal.html';

        $this->template = $this->getTemplate($templateLink);
    }

    /**
     * Renders the modal content
     * 
     * @return string HTML code
     */
    public function render() {
        $this->template->modal_id = $this->id;
        $this->template->modal_title = $this->title;
        $this->template->modal_content = $this->content;
        $this->template->modal_close_button = $this->createCloseButton();
        $this->template->scripts = $this->createScripts();

        return $this->template->render()->getRenderedContent();
    }

    /**
     * Creates modal close button HTML code
     * 
     * @return string HTML code
     */
    private function createCloseButton() {
        return '<a class="grid-link" href="#" onclick="' . $this->componentName . '_closeModal();">&times;</a>';
    }

    /**
     * Creates necessary JS scripts for the modal
     * 
     * @return string HTML code
     */
    private function createScripts() {
        $this->scripts[] = '
            <script type="text/javascript">
                function ' . $this->componentName . '_closeModal() {
                   $("#' . $this->id . '-modal-inner").css("visibility", "hidden")
                        .css("height", "0px");
                }
            </script>
        ';

        return implode('', $this->scripts);
    }

    public static function createFromComponent(AComponent $component) {
        $obj = new self($component->httpRequest);
        $obj->setApplication($component->app);
        $obj->setPresenter($component->presenter);

        return $obj;
    }
}

?>