# SkyDocu
SkyDocu is a web DMS that has been created to be run in a cloud. It has a superadministration where the superadministrator can manage containers.
Containers are instances of DMS applications for customers.

Current version: ___1.3.1___

Latest version: ___1.3.1___ (February 26th, 2025)

## Tech stack
The web application is written purely in PHP with a few JS scripts. These JS scripts are mostly used for AJAX and other dynamic behavior functions.

## Future plans
These future plans are also in Issues list and can be distinguished by their milestone.

Future plans include:
- Dark theme support (__1.4__)
- Grid exporting (__1.4__)
- Multiple database servers (__1.4__, may be postponed)

## Changelog
### 1.3.1 (February 26th, 2025)
- About application widget update
    - Version release date formatting
- Code comments update

### 1.3 (February 26th, 2025)
- Grid searching
    - Containers grid
    - In-container Documents grid
    - In-container Users grid
    - In-conatiner Groups grid
- Cache invalidation bug fix
- FoldersSidebar alphabetical sorting
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

### 1.2 (January 27th, 2025)
- Code readability update
- Superadministration about application page
- ErrorModule implementation
- Core framework updates
- Bugfixes
- GridBuilder optimization
- Form reducing & on change AJAX calling capability
- Processes grid design update
- Document sharing
- Core AJAX requests and responses update
- Updated config.php define checking
- Empty GridBuilder design update
- Accessibility update
    - GridBuilder column name hints
    - GridBuilder control hints
    - Link hints
    - FormBuilder element hints
- Updated information flash message design
- User absence & substitution

### 1.1 (January 7th, 2025)
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

### 1.0 (November 29th, 2024)
- Container management
- Container usage statistics
- Container functions
    - Document management
    - User management
    - Group management
    - Document folders management
    - Custom metadata management