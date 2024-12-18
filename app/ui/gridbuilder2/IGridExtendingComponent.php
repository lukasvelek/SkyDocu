<?php

namespace App\UI\GridBuilder2;

/**
 * Interface used to distinguish whether the grid component is extending the default GridBuilder
 * 
 * @author Lukas Velek
 */
interface IGridExtendingComponent {
    /**
     * Creates data source for the grid
     * 
     * @internal
     */
    function createDataSource();
}

?>