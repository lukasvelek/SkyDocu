<?php

namespace App\Components\ArchiveFoldersSidebar;

use App\Components\Sidebar\Sidebar2;
use App\Core\Http\HttpRequest;
use App\Managers\Container\ArchiveManager;

/**
 * Archive folders sidebar component shows all archive folders
 * 
 * @author Lukas Velek
 */
class ArchiveFoldersSidebar extends Sidebar2 {
    private ArchiveManager $archiveManager;
    private string $action;
    private array $_list;

    public function __construct(HttpRequest $request, ArchiveManager $archiveManager, string $action) {
        parent::__construct($request);

        $this->setComponentName('foldersSidebar');

        $this->archiveManager = $archiveManager;
        $this->action = $action;
        
        $this->_list = [];
    }

    public function startup() {
        parent::startup();

        $folders = $this->archiveManager->getAllArchiveFolders();

        $list = [];
        foreach($folders as $folder) {
            $this->createFolderList(new ArchiveFolder($folder), $list, 0);
        }

        $this->links = $this->processList($list);
    }

    /**
     * Processes the folder link list and sorts it alphabetically
     * 
     * @return array Folder link list
     */
    private function processList(array $list) {
        $links = [];

        $titles = [];
        foreach($list as $l) {
            /**
             * @var ArchiveFolderLink $l
             */
            if(!in_array($l->getTitle(), $titles)) {
                $titles[] = $l->getTitle();
            }
        }

        sort($titles);

        foreach($titles as $title) {
            foreach($list as $l) {
                /**
                 * @var ArchiveFolderLink $l
                 */

                if($l->getTitle() == $title) {
                    $links[] = $l->getLink();
                }
            }
        }

        return $links;
    }

    public function prerender() {
        parent::prerender();

        if(!empty($this->staticLinks)) {
            array_unshift($this->links, '<hr>');

            foreach($this->staticLinks as $link) {
                array_unshift($this->links, $link);
            }
        }
    }

    /**
     * Creates folder list recursively
     * 
     * @param Folder $folder Current folder entity
     * @param array $list Current link list
     * @param int $level Current nesting level
     * @param bool $isDefault Is default? (DO NOT USE)
     */
    private function createFolderList(ArchiveFolder $folder, array &$list, int $level, bool $isDefault = false) {
        $subfolders = $this->archiveManager->getSubfoldersForArchiveFolder($folder->folderId);

        if($folder->row->title == 'Default') {
            $isDefault = true;
        }

        $title = $folder->row->title;
        $params = [];

        if($folder->row->title != 'Default') {
            $params = ['folderId' => $folder->folderId];
        }

        $active = false;
        if($this->httpRequest->query('folderId') !== null && $this->httpRequest->query('folderId') == $folder->folderId) {
            $active = true;
        } else if($this->httpRequest->query('folderId') === null && $folder->row->title == 'Default' && $this->httpRequest->query('action') == 'list') {
            $active = true;
        }

        $link = $this->createLink($title, $this->presenter->createURL($this->action, $params), $active);
        $link = new ArchiveFolderLink($folder->row->title, $link);

        if($level > 0) {
            $space = 5 * $level; // 5px * nesting level -> more subtle than &nbsp;&nbsp;

            $link->updateLinkWithSpacing('<span style="margin-left: ' . $space . 'px">', '</span>');
        }

        if($isDefault === true) {
            array_unshift($list, $link);
        } else {
            $list[] = $link;
        }

        $this->_list[$folder->parentFolderId ?? 'null'][] = $folder;

        if(count($subfolders) > 0) {
            foreach($subfolders as $subfolder) {
                $this->createFolderList(new ArchiveFolder($subfolder), $list, $level + 1, $isDefault);
            }
        }
    }
}

?>