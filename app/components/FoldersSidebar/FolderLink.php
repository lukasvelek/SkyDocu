<?php

namespace App\Components\FoldersSidebar;

/**
 * This class represents a link to a folder
 * 
 * @author Lukas Velek
 */
class FolderLink {
    private string $title;
    private string $link;

    /**
     * Class constructor
     * 
     * @param string $title Title
     * @param string $link Link
     */
    public function __construct(string $title, string $link) {
        $this->title = $title;
        $this->link = $link;
    }

    /**
     * Returns the folder's title
     * 
     * @return string Folder's title
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * Returns the folder's link
     * 
     * @return string Folder's link
     */
    public function getLink(): string {
        return $this->link;
    }

    /**
     * Updates the folder's link with spacing
     * 
     * @param string $codeBefore Code that goes before the link
     * @param string $codeAfter Code that goes after the link
     */
    public function updateLinkWithSpacing(string $codeBefore, string $codeAfter) {
        $this->link = $codeBefore . $this->link . $codeAfter;
    }
}

?>