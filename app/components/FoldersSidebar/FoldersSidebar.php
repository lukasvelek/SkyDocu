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

    public function __construct(HttpRequest $request, FolderManager $folderManager, string $action) {
        parent::__construct($request);

        $this->folderManager = $folderManager;
        $this->action = $action;
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
     */
    private function createFolderList(Folder $folder, array &$list, int $level) {
        $subfolders = $this->folderManager->getSubfoldersForFolder($folder->folderId);

        $spaces = '';
        if($level > 0) {
            for($i = 0; $i < $level; $i++) {
                $spaces .= '&nbsp;&nbsp;';
            }
        }

        $title = $spaces . $folder->row->title;
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

        $list[] = $this->createLink($title, $this->presenter->createURL($this->action, $params), $active);

        if(count($subfolders) > 0) {
            foreach($subfolders as $subfolder) {
                $this->createFolderList(new Folder($subfolder), $list, $level + 1);
            }
        }
    }
}

?>