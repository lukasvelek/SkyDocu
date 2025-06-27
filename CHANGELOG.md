# SkyDocu Changelog
Previously changelog for versions `1.0`-`1.4` has been located in README.md. However that location wasn't a very good option and therefore it has been moved here.

## Index
[SkyDocu `1.7` (_WIP_)](#skydocu-17-wip)  
[SkyDocu `1.6` (06/21/2025)](#skydocu-16-06212025)  
[SkyDocu `1.5` (04/08/2025)](#skydocu-15-04082025)  
[SkyDocu `1.4` (03/22/2025)](#skydocu-14-03222025)  
[SkyDocu `1.3.1` (02/26/2025)](#skydocu-131-02262025)  
[SkyDocu `1.3` (02/26/2025)](#skydocu-13-02262025)  
[SkyDocu `1.2` (01/27/2025)](#skydocu-12-01272025)  
[SkyDocu `1.1` (01/07/2025)](#skydocu-11-01072025)  
[SkyDocu `1.0` (11/29/2024)](#skydocu-10-11292024)

## SkyDocu `1.7` (_WIP_)
- Added support for custom processes in containers
- Added process editor to containers
- Added $CURRENT_USER$ as the actor of the first process step
- Users update
    - Added container technical account creation from Superadministration
- Fixed a bug where custom container processes are visible in container settings in superadministration
- Added support for technical user creation in containers in superadministration
- Removed support for container environments
    - Because SkyDocu itself is split to environments (PROD, TEST, DEV)

## SkyDocu `1.6` (06/21/2025)
- Global transaction log
    - All transactions are logged into a single master transaction log
- Implemented PeeQL to API
- Updated process information page
- Updated ordering in process grids
- Added support for user custom date and time formats
- Added conversion tool for JSON form definitions
    - The tool receives JSON form definition and returns the FormBuilder or HTML code of the form
- Processes overhaul
    - Updated workflow
    - Custom process types
    - Multistep forms
    - Process editor
    - Process versioning
    - Support for files
    - Process metadata
- Job Queue
    - Certain jobs should be performed in background
    - Process version adding to distribution
    - Process instance data removal
    - Changing process visibility from Superadministration
    - Added background service
- Removed property management

## SkyDocu `1.5` (04/08/2025)
- Added _Requested containers_ metric to Superadministration Container statistics widget
- Code cleanup
- API endpoints
- External systems
    - Right management
    - Logging
- New application version format implementation
    - Build and branch are now included
- `ContainerFileRemovingSlaveService` update
- General services update
- Bugfixes
- Global usage graphs
- Archive folder removing
- In-container process reports design update
- Navigation bar bugfix
- Container user management
- In-container property managament
- In-container administration dashboard widget update

## SkyDocu `1.4` (03/22/2025)
- In-container multiple database support
    - Database management
    - Database table scheme browser
    - Database table data browser
- Moving documents between folders
- Dark theme support
- Background services rework
    - Automatic running
    - Service scheduling
    - Service master & slave
- Database migrations & initial seeding
- Container distribution
    - Containers can now be removed from distribution
- Multiple bugfixes
- Renamed view names for container Process views
- Cache bugfix
- Flash messages update

## SkyDocu `1.3.1` (02/26/2025)
- About application widget update
    - Version release date formatting
- Code comments update
- Bugfixes

## SkyDocu `1.3` (02/26/2025)
- Grid searching
    - Containers grid
    - In-container Documents grid
    - In-container Users grid
    - In-conatiner Groups grid
- Cache invalidation bug fix
- `FoldersSidebar` alphabetical sorting
- New widgets
- All process rights update
- Archive
    - Archive folders
    - Document archivation
- Component skeletons
    - More user-friendly component loading or refreshing animation
- Document file uploading
    - Uploaded file overview (in grid) + stats in in-container administration
- Invoice Process
- CotnainerRequest Process
- Bugfixes
- Exception page
- Process metadata
- Process comments
- File storing
    - Upload files and associate them with documents
    - Download files
    - File storage overview
- Process profile page design update
- In-application versioning update

## SkyDocu `1.2` (01/27/2025)
- Code readability update
- Superadministration about application page
- ErrorModule implementation
- Core framework updates
- Bugfixes
- `GridBuilder` optimization
- Form reducing & on change AJAX calling capability
- Processes grid design update
- Document sharing
- Core AJAX requests and responses update
- Updated config.php define checking
- Empty `GridBuilder` design update
- Accessibility update
    - `GridBuilder` column name hints
    - `GridBuilder` control hints
    - Link hints
    - FormBuilder element hints
- Updated information flash message design
- User absence & substitution

## SkyDocu `1.1` (01/07/2025)
- Document processes
- Standalone processes
- Bugfixes
- UI core updates
- Standalone process reports
- User profile in container section
- Container invites
    - Link generation
    - User registration
    - User registration management - accept, reject, delete
- Grid constant automatic color support
- Documents grid filtering
- Processes grid filtering
- Grid filter saving
- Deprecated components removed
    - FormBuilder 1.0 (FormBuilder 2.0 is currently being used)
- UI finished (all parts of UI should be avialable and visible (according to the visibility rights))

## SkyDocu `1.0` (11/29/2024)
- Container management
- Container usage statistics
- Container functions
    - Document management
    - User management
    - Group management
    - Document folders management
    - Custom metadata management