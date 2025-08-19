<?php

namespace App\Components\ArchiveFoldersSidebar;

/**
 * This class represents a link to an archive folder
 * 
 * @author Lukas Velek
 */
class ArchiveFolderLink {
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
     * Returns the archive folder's title
     * 
     * @return string Folder's title
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * Returns the archive folder's link
     * 
     * @return string Folder's link
     */
    public function getLink(): string {
        return $this->link;
    }

    /**
     * Updates the archive folder's link with spacing
     * 
     * @param string $codeBefore Code that goes before the link
     * @param string $codeAfter Code that goes after the link
     */
    public function updateLinkWithSpacing(string $codeBefore, string $codeAfter) {
        $this->link = $codeBefore . $this->link . $codeAfter;
    }
}

?>