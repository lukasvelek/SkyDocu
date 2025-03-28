# SkyDocu - Programming documentation
This documentation contains useful information for SkyDocu development. It is still being worked on and may contain untrue information.

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
[`3.2.1` FormBuilder](#321-formbuilder)  
[`3.2.2` GridBuilder](#322-gridbuilder)  
[`3.2.3` ListBuilder](#323-listbuilder)  
[`3.2.4` ModalBuilder](#324-modalbuilder)  
[`3.2.5` HTML](#325-html)  
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

I have also made an on-premises DMS that has been an inspiration for this project. Although it's not similar in any way.

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
Core UI components are used in advanced components (see section `3.3` Components). These components allow creating basic tables, forms, modals and links.

They usually contain basic and general functionalities.

#### `3.2.1` FormBuilder
FormBuilder allows creating interactive forms. All the functionality is in `FormBuilder` class.

#### `3.2.2` GridBuilder
GridBuilder allows creating tables with data that come from database. These are usually dynamic, paginated tables.

#### `3.2.3` ListBuilder
ListBuilder allows creating static tables with static data. No pagination or searching is allowed.

#### `3.2.4` ModalBuilder
ModalBuilder allows creating modal windows. It is not used that much.

#### `3.2.5` HTML
HTML is not a UI component per se but it's more of a library with useful functions for creating or composing HTML tags.

### `3.3` Components
Components are extensions to core UI components.

E.g. core UI component is a GridBuilder that is used for creating tables with database data source. For documents there is DocumentsGrid and that is an extension for GridBuilder.

Core UI components can therefore be used by multiple parts of the application. Whereas components usually are in a single-use scenario.

## `4` Background services
Background services have recently been reworked. Overview and management of background services is available only in Superadministration, however they access containers and operate on them.

Each background service is divided into two scripts. First is the entry point for the CMD and the second is the service process definition in the application. That means that the first one is run from the command line (executed straight by `php.exe`) and the second one defines what does the service actually do.

Currently there are several services but the most important are `LogRotateService` and `ContainerCreationService`.

Some of the services are split to a master and a slave. When the service is run (either from UI or by scheduler) the master is started. The master then retrieves information needed and delegates it two separate slaves.

The master-slave principle is best described in [`4.2` `ContainerCreationService` service section](#42-containercreationservice-service)

### `4.1` `LogRotateService` service
`LogRotateService` goes through all log files and those that were created before the current day are put into their separate folders.

For example:
Let's presume that today is June 1st.

Log files older that June 1st will be grouped by their date and put into folders.

So, all log files from May 31st would be put to _2025/05/31_ folder.

This principle works for all log types.

### `4.2` `ContainerCreationService` service
This is one of the recently reworked services. Now it is divided into a master and a slave.

Its purpose is to create containers.

When the service is run (either from UI or by scheduler) the master is started. It retrieves all the containers that are meant to be created and starts a slave for each container. Each slave then creates the container itself.

## API
### API endpoints
- api/v1/login/ - Login and get token
    - Parameters:
        - login
        - password
        - containerId
    - Returns:
        - token

- api/v1/users/get/ - Get single user
    - Parameters:
        - token
        - userId
        - properties
            - userId
            - username
            - fullname
            - dateCreated
            - email
            - isTechnical
            - appDesignTheme

- api/v1/users/get/ - Get all users
    - Parameters:
        - token
        - limit
        - offset
        - properties
            - userId
            - username
            - fullname
            - dateCreated
            - email
            - isTechnical
            - appDesignTheme

- api/v1/documents/get/ - Get single document
    - Parameters:
        - token
        - documentId
        - properties
            - documentId
            - title
            - authorUserId
            - description
            - status
            - classId
            - folderId
            - dateCreated
            - dateModified

- api/v1/documents/get/ - Get all documents
    - Parameters:
        - token
        - limit
        - offset
        - properties
            - documentId
            - title
            - authorUserId
            - description
            - status
            - classId
            - folderId
            - dateCreated
            - dateModified

- api/v1/processes/get/ - Get single process
    - Parameters:
        - token
        - processId
        - properties
            - processId
            - documentId
            - type
            - authorUserId
            - currentOfficerUserId
            - workflowUserIds
            - dateCreated
            - status
            - currentOfficerSubstituteUserId

- api/v1/processes/get/ - Get all processes
    - Parameters:
        - token
        - limit
        - offset
        - properties
            - processId
            - documentId
            - type
            - authorUserId
            - currentOfficerUserId
            - workflowUserIds
            - dateCreated
            - status
            - currentOfficerSubstituteUserId

- api/v1/documents/create/ - Create a document
    - Parameters:
        - token
        - title
        - classId
        - authorUserId
        - folderId
    - Optional parameters:
        - description
    - Returns:
        - documentId

## Database

### Database table schema
### Database connection

## Caching

## Logging