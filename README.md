# SkyDocu
SkyDocu is a web DMS that has been created to be run in a cloud. It has a superadministration where the superadministrator can manage containers.
Containers are instances of DMS applications for customers.

Current version: ___1.2-dev___
Latest version: ___1.1___ (January 7th, 2025)

## Tech stack
The web application is written purely in PHP with a few JS scripts. These JS scripts are mostly used for AJAX and other dynamic behavior functions.

## Changelog
### 1.2 (?)
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