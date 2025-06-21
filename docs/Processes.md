# SkyDocu - Processes
This document is about processes in SkyDocu. Here you can find all information about processes generally, the process instance lifecycle and the process editor in superadministration.

## Index
- [`#1` About processes](#1-about-processes)
- [`#2` Process instance lifecycle](#2-process-instance-lifecycle)
- [`#3` Process editor](#3-process-editor)
    - [`#3.1` Available form elements](#31-available-form-elements)
    - [`#3.2` Available form actors](#32-available-form-actors)
    - [`#3.3` Process form rules](#33-process-form-rules)
    - [`#3.4` Service step](#34-service-step)
      - [`#3.4.1` `ProcessServiceUserHandlingService` background service](#341-processserviceuserhandlingservice-background-service)
    - [`#3.5` Process form JSON template](#35-process-form-json-template)
      - [`#3.5.1` Simple two field form](#351-simple-two-field-form)
      - [`#3.5.2` Accept/reject form](#352-acceptreject-form)
      - [`#3.5.3` Service step definition](#353-service-step-definition)

## `#1` About processes
Processes in SkyDocu represent an action or request with a lifecycle.

As of version `1.6` all processes are created system-wide and are available for all containers. However they can be disabled in certain containers.

Processes are composed of forms and actions. Each form is available for given actor or officer and the flow is defined in the process editor.

## `#2` Process instance lifecycle
Process instance is created right after the user opens a process. However the instance is not saved and is held only in memory. User then fills the form and submits it.

Then the instance is saved to the database. After that the next officer is evaluated and the instance is assigned to them.

The next officer opens the instance and fills the form and either submits it to the next user or processes it with predefined action buttons - e.g. accept, reject, cancel, etc.

The last officer must have at least one of the predefined action buttons, so the instance can be finished and closed.

If any officer is a _$SERVICE_USER$_ then the system handles the defined operations.

## `#3` Process editor
As of version `1.6` the process editor is only available in the superadministration.

It is used to define a process workflow with form for each step.

Steps can be processed by users or by the system's _service user_.

The form is defined using JSON.

### `#3.1` Available form elements
Here is the list of all available form elements:
- `label`
    - general information label
- `text`
    - text field
- `password`
    - password field
- `number`
    - number field
- `select`
    - select field
- `checkbox`
    - checkbox field
- `date`
    - date field
- `datetime`
    - date and time field
- `time`
    - time field
- `file`
    - file upload field
- `email`
    - email field
- `textarea`
    - multi-lane text field
- `submit`
    - submit button
- `button`
    - general button
- `userSelect`
    - user select field combo
- `userSelectSearch`
    - user select field combo
        - contains a text field with a search button and a select field with found results
- `selectSearch`
    - general select field combo
        - contains a text field with a search button and a select field with found results
- `documentSelectSearch`
    - document select field combo
        - contains a text field with a search button and a select field with found results
- `processSelectSearch`
    - process select field combo
        - contains a text field with a search button and a select field with found results
- `cancelButton`
    - cancel button
- `finishButton`
    - finish button
- `archiveButton`
    - archive button
- `acceptButton`
    - accept button
- `rejectButton`
    - reject button

### `#3.2` Available form actors
Here is the list of all available form actors:

- \$CURRENT_USER\$
    - current user

- \$SERVICE_USER\$
    - service user
    - this actor means the process will be processed by system

- \$CURRENT_USER_SUPERIOR\$
    - current user's superior
    - if current user has no superior then current user is the actor

- \$INSTANCE_AUTHOR\$
    - process instance author
    - the author of the process instance

- \$ACCOUNTANTS\$
    - Accountants group

- \$ADMINISTRATORS\$
    - Administrators group

- \$ARCHIVISTS\$
    - Archivists group

There are also two special actors:
- \$UID_`USER_ID`\$
    - represents a specific static user
    - replace `USER_ID` with user's ID

- \$GID_`GROUP_ID`\$
    - represents a specific static group
    - replace `GROUP_ID` with group's ID

### `#3.3` Process form rules
There are also some rules that must be respected in order for the form to work correctly.

Here is a list of rules:  
1. The first form has no buttons
    - There is only one action available and that is __Submit__
    - The next forms must have at least one button
2. Service steps (steps with _$SERVICE_USER$_ being the actor) are used only for:
    - modifying the instance description
    - modifying the instance status

### `#3.4` Service step
Service step is a form whose actor is _$SERVICE_USER$_. This is actually not called a form but a step, because the service step is automatically handled by system.

A service step can perform two things:  
1. Change process instance description
2. Change process instance status
    - Possible values are:
        - `1` - _New_
        - `2` - _In progress_
        - `3` - _Canceled_
        - `4` - _Finished_
        - `5` - _Archived_
    - Although the `1` - _New_ value is available it should not be used as it doesn't make sense for the form to return back to this state
        - This might be change in future versions

#### `#3.4.1` `ProcessServiceUserHandlingService` background service
The service step - as said - is handled by the system. When the last actor before the service step submits the form, the next officer is evaluated and if the system finds out that it is a service step, then a background service `psuhs` or `ProcessServiceUserHandlingService` is started.

Once the background service is started, it looks in the database and searches for all process instances awaiting for system processing.

If any process instances are found, the service processes them one by one. Each of the process instance is loaded and the operations, that should be performed by system, are found and performed.

Then the next officer is evaluated and the process instance is either terminated or moved to the next officer.

### `#3.5` Process form JSON template
#### `#3.5.1` Simple two field form
Here is the JSON definition for a form with two elements - a `text` field and a `textarea` field.

```
{
    "name": "myRequest",
    "elements": [
        {
            "name": "title",
            "label": "Title:",
            "type": "text",
            "attributes": [
                "required"
            ]
        },
        {
            "name": "description",
            "label": "Description:",
            "type": "textarea",
            "attributes": [
                "required"
            ]
        }
    ]
}
```

#### `#3.5.2` Accept/reject form
Here is a JSON definition for a form with a `textarea` field and two buttons - `accept` button and `reject` button:
```
{
    "name": "myRequestHandling",
    "elements": [
        {
            "name": "message",
            "label": "Message:",
            "type": "textarea"
        },
        {
            "name": "accept",
            "type": "acceptButton"
        },
        {
            "name": "reject",
            "type": "rejectButton"
        }
    ]
}
```

#### `#3.5.3` Service step definition
Here is a JSON definition for a service step that changes the instance status to `3` (=_Canceled_) and instance description:
```
{
    "name": "changeData",
    "operations": [
        "status": 3,
        "instanceDescription": "My own description"
    ]
}
```