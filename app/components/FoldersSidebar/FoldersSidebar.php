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
    private array $positions;
    private array $_list;

    public function __construct(HttpRequest $request, FolderManager $folderManager, string $action) {
        parent::__construct($request);

        $this->folderManager = $folderManager;
        $this->action = $action;

        $this->positions = [];
        $this->_list = [];
    }

    public function startup() {
        parent::startup();

        $visibleFolders = $this->folderManager->getVisibleFoldersForUser($this->presenter->getUserId());

        $list = [];
        foreach($visibleFolders as $vf) {
            $this->createFolderList(new Folder($vf), $list, 0);
        }

        $this->links = $list;
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
        if(array_key_exists('folderId', $this->httpRequest->query) && $this->httpRequest->query['folderId'] == $folder->folderId) {
            $active = true;
        } else if(!array_key_exists('folderId', $this->httpRequest->query) && $folder->row->title == 'Default') {
            $active = true;
        }

        $link = $this->createLink($title, $this->presenter->createURL($this->action, $params), $active);

        if($level > 0) {
            $space = 5 * $level; // 5px * nesting level -> more subtle than &nbsp;&nbsp;

            $link = '<span style="margin-left: ' . $space . 'px">' . $link . '</span>';
        }

        if($isDefault === true) {
            array_splice($list, $level, 0, $link);
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