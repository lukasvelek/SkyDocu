<?php

namespace App\Helpers;

/**
 * LinkHelper helps with links
 * 
 * @author Lukas Velek
 */
class LinkHelper {
    /**
     * Creates links from array
     * 
     * @param array $links Links
     */
    public static function createLinksFromArray(array $links): string {
        return implode('&nbsp;&nbsp;', $links);
    }
}

?>