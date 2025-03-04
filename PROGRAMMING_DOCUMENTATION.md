# SkyDocu - Programming documentation

This documentation contains useful information for SkyDocu development.

## `0` Index
Here is the list of all chapters.

[`1` About SkyDocu](#1-about-skydocu)  
[`1.1` Superadministration](#11-superadministration)  
[`1.2` Containers](#12-containers)  
[`2` Technologies behind SkyDocu](#2-technologies-behind-skydocu)  
[`3` Frontend](#3-frontend)  
[`3.1` Modules & Presenters](#31-modules--presenters)  
[`3.1.1` AdminModule](#311-adminmodule)  
[`3.1.2` UserModule](#312-usermodule)  
[`3.1.3` AnonymModule](#313-anonymmodule)  
[`3.1.4` ErrorModule](#314-errormodule)  
[`3.1.5` SuperAdminModule](#315-superadminmodule)  
[`3.1.6` SuperAdminSettingsModule](#316-superadminsettingsmodule)  
[`3.2` Core UI components](#32-core-ui-components)  
[`3.3` Components](#33-components)  

## `1` About SkyDocu
SkyDocu is a Document Management System (further referred to as "DMS"). It is not a traditional DMS because it is created as a cloud application.

    Currently it is intended to be run on-premise as it has not been tested in a cloud environment (Amazon AWS, MS Azure, etc.). It is still being developed and support for those cloud providers might be added in the future.

The application is divided into two sections - __Superadministration__ and __Containers__. The __superadministration__ is used for managing containers. A __container__ is the DMS itself.

The basic premise is that containers are for companies/clients that require DMS capabilities.

### `1.1` Superadministration
Superadministration is only for superadministrators as it allows managing containers. Here you can create a container, schedule or run background services, manage users and groups, etc.

There are several widgets with stats for each individual container.

### `1.2` Containers
Container is the DMS itself. Here users manage documents and start processes.

Each container also has its own administration section where administrators are allowed to manage users, groups, container invites, file storage, data sources, etc.

## `2` Technologies behind SkyDocu
SkyDocu is written mostly in PHP with some dynamic functionalities written in JavaScript.

The main framework used in the application is taken from another project and upgraded for more complex functionality. All other code is either taken from other projects and updated or written from scratch.

I have also made a on-premises DMS that has been an inspiration for this project. Although it's not similar in any way.

There are three external libraries used:
- Bootstrap
    - for CSS designing
- jQuery
    - for AJAX and extended JS capabilities
- Chart.js
    - for graphs

## `3` Frontend
Frontend is divided into several categories:
- Modules & Presenters
- Core UI components
- Components

### `3.1` Modules & Presenters
The main part and the definition of the UI pages are modules and presenters.

Module is a category of pages of the application. There are currently 5 modules:
- AdminModule
- UserModule
- AnonymModule
- ErrorModule
- SuperAdminModule
- SuperAdminSettingsModule

Each module is further divided into presenters. Presenter is then divided into actions. However actions in a presenter should have a common meaning.

E.g. in presenter named _UserPrenter_ I should have actions that have something to do with a user. For example profile, user editing, etc.

Which module is used can be found in the URL:  
`?page=SuperAdmin:Home&action=home`  
The page is _HomePresenter_ in _SuperAdminModule_ and the action is _home_.

#### `3.1.1` AdminModule
AdminModule contains pages (presenters) for in-container administration. This is accessible only for administrators.

#### `3.1.2` UserModule
UserModule contains everything that has something to do with DMS. It is accessible for all users in a container.

#### `3.1.3` AnonymModule
AnonymModule contains everything that is accessible for a anonymouser user. This is currently only login form.

#### `3.1.4` ErrorModule
ErrorModule contains presenters responsible for displaying information about raised exceptions or other errors.

#### `3.1.5` SuperAdminModule
SuperAdminModule is available for superadministrators and allows creating containers and configuring them.

#### `3.1.6` SuperAdminSettingsModule
SuperAdminSettingsModule is used for managing the application or superadministration itself.

### `3.2` Core UI components
### `3.3` Components