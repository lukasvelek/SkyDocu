<?php

namespace App\Components\ContainerSelectionForm;

use App\Constants\ContainerEnvironments;
use App\Constants\ContainerStatus;
use App\Core\AjaxRequestBuilder;
use App\Core\Http\HttpRequest;
use App\UI\FormBuilder2\FormBuilder2;

class ContainerSelectionForm extends FormBuilder2 {
    private const MAX_CONTAINER_COUNT_FOR_SEARCH_FORM = 5;

    private bool $useSearchForm;

    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->componentName = 'ContainerSelectionForm';

        $this->useSearchForm = false;
    }

    public function setContainerCount(int $count) {
        if($count >= self::MAX_CONTAINER_COUNT_FOR_SEARCH_FORM) {
            $this->useSearchForm = true;
        }
    }

    public function render() {
        $this->setup();
        $this->createScripts();

        return parent::render();
    }

    private function setup() {
        if($this->useSearchForm) {
            $this->createSearchForm();
        } else {
            $this->createGeneralForm();
        }

        $this->addSubmit('Select');
    }

    private function createSearchForm() {
        $lastContainer = 'null';
        if(isset($this->httpRequest->query['lastContainer'])) {
            $lastContainer = $this->httpRequest->query['lastContainer'];
        }

        $this->addTextInput('containerSearch', 'Search container:')
            ->setRequired();

        $this->addButton('Search')
            ->setOnClick('searchContainers(\'' . $lastContainer . '\')');

        $this->addSelect('container', 'Container:')
            ->setDisabled();
    }

    private function createGeneralForm() {
        $containers = $this->getContainers();
        $disabled = empty($containers);

        $this->addSelect('container', 'Container:')
            ->setRequired()
            ->addRawOptions($containers)
            ->setDisabled($disabled);
    }

    private function createScripts() {
        $arb = new AjaxRequestBuilder();
        
        $arb->setMethod()
            ->setComponentAction($this->presenter, $this->componentName . '-searchContainers')
            ->setHeader(['query' => '_query', 'lastContainer' => '_lastContainer'])
            ->setFunctionName('getContainers')
            ->setFunctionArguments(['_query', '_lastContainer'])
            ->updateHTMLElement('container', 'containers');

        $this->addScript($arb->build());
        
        // Search button handler
        $code = 'function searchContainers(_lastContainer) {
            var _query = $("#containerSearch").val();

            if(_query.length == 0) {
                alert("No container name entered.");
            } else {
                getContainers(_query, _lastContainer);
            $("#container").removeAttr("disabled");
            }
        }';

        $this->addScript($code);
    }

    protected function actionSearchContainers() {
        $query = $this->httpRequest->query['query'];

        $containers = $this->getContainers($query);

        $code = [];
        foreach($containers as $c) {
            $selected = '';
            if(array_key_exists('selected', $c)) {
                $selected = ' selected';
            }

            $code[] = '<option value="' . $c['value'] . '"' . $selected . '>' . $c['text'] . '</option>';
        }

        return ['containers' => implode('', $code)];
    }

    private function getContainers(?string $query = null) {
        $groups = $this->app->groupManager->getMembershipsForUser($this->presenter->getUserId());

        $containers = [];
        foreach($groups as $group) {
            if($group->containerId !== null) {
                $container = $this->app->containerManager->getContainerById($group->containerId);

                if($container->status == ContainerStatus::NEW || $container->status == ContainerStatus::IS_BEING_CREATED || $container->status == ContainerStatus::NOT_RUNNING) {
                    continue;
                }

                if($query !== null) {
                    if(!str_contains($group->title, $query) && !str_contains(strtolower($group->title), strtolower($query)) && ($group->title != 'superadministrators')) {
                        continue;
                    }
                }
            }

            if($group->title == 'superadministrators') {
                $c = [
                    'value' => $group->title,
                    'text' => 'Superadministration'
                ];

                array_unshift($containers, $c);
            } else {
                $title = substr($group->title, 0, (strlen($group->title) - 8)) . ' (' . ContainerEnvironments::toString($container->environment) . ')';

                $c = [
                    'value' => $group->containerId,
                    'text' => $title
                ];

                if(array_key_exists('lastContainer', $this->httpRequest->query)) {
                    if($group->containerId == $this->httpRequest->query['lastContainer']) {
                        $c['selected'] = 'selected';
                    }
                }

                $containers[] = $c;
            }
        }

        return $containers;
    }
}

?>