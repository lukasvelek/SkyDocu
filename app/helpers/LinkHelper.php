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
     * @param string $separator Separator
     */
    public static function createLinksFromArray(array $links, string $separator = '&nbsp;&nbsp;'): string {
        return implode($separator, $links);
    }
}

?>