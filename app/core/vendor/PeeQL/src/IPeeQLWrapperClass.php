<?php

namespace PeeQL;

/**
 * Interface for wrapping classes around PeeQL\PeeQL class
 * 
 * @author Lukas Velek
 */
interface IPeeQLWrapperClass {
    /**
     * Processes the given JSON $json query, executes it and returns the result
     * 
     * @param string $json JSON query
     */
    public function execute(string $json): mixed;
}

?>