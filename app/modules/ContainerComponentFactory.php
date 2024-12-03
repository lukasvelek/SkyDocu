<?php

namespace App\Modules;

use App\Core\Http\HttpRequest;

class ContainerComponentFactory extends ComponentFactory {
    protected AContainerPresenter $presenter;

    public function __construct(HttpRequest $request, AContainerPresenter $presenter) {
        $this->request = $request;
        $this->presenter = $presenter;
    }
}

?>