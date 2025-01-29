<?php

namespace App\Components\FoldersSidebar;

use App\Components\Sidebar\Sidebar2;
use App\Core\Http\HttpRequest;
use App\Managers\Container\FolderManager;

/**
 * Folders sidebar component shows all visible folders to current user
 * 
 * @author Lukas Velek
 */
class FoldersSidebar extends Sidebar2 {
    private FolderManager $folderManager;
    private string $action;
    private array $_list;

    public function __construct(HttpRequest $request, FolderManager $folderManager, string $action) {
        parent::__construct($request);

        $this->setComponentName('foldersSidebar');

        $this->folderManager = $folderManager;
        $this->action = $action;
        
        $this->_list = [];
    }

    public function startup() {
        parent::startup();

        $visibleFolders = $this->folderManager->getVisibleFoldersForUser($this->presenter->getUserId());

        $list = [];
        foreach($visibleFolders as $vf) {
            $this->createFolderList(new Folder($vf), $list, 0);
        }

        /** CUSTOM STATIC LINKS */

        $this->addStaticLink('Shared documents', $this->createFullURL('User:Documents', 'listShared'), $this->checkIsLinkActive(['page' => 'User:Documents', 'action' => 'listShared']));

        /** END OF CUSTOM STATIC LINKS */

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
             * @var FolderLink $l
             */
            if(!in_array($l->getTitle(), $titles)) {
                $titles[] = $l->getTitle();
            }
        }

        sort($titles);

        foreach($titles as $title) {
            foreach($list as $l) {
                /**
                 * @var FolderLink $l
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
    private function createFolderList(Folder $folder, array &$list, int $level, bool $isDefault = false) {
        $subfolders = $this->folderManager->getSubfoldersForFolder($folder->folderId);

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
        $link = new FolderLink($folder->row->title, $link);

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
                $this->createFolderList(new Folder($subfolder), $list, $level + 1, $isDefault);
            }
        }
    }
}

?>