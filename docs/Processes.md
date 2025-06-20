# SkyDocu - Processes
This document is about processes in SkyDocu. Here you can find all information about processes generally, the process instance lifecycle and the process editor in superadministration.

## Index
- [`#1` About processes](#1-about-processes)
- [`#2` Process instance lifecycle](#2-process-instance-lifecycle)
- [`#3` Process editor](#3-process-editor)
    - [`#3.1` Available form elements](#31-available-form-elements)
    - [`#3.2` Available form actors](#32-available-form-actors)
    - [`#3.3` Process form JSON template](#33-process-form-json-template)
        - [`#3.3.1` Simple two field form](#331-simple-two-field-form)
    - [`#3.4` Process form rules](#34-process-form-rules)

## `#1` About processes
Processes in SkyDocu represent an action or request with a lifecycle.

As of version `1.6` all processes are created system-wide and are available for all containers. However they can be disabled in certain containers.

Processes are composed of forms and actions. Each form is available for given actor or officer and the flow is defined in the process editor.

## `#2` Process instance lifecycle

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

### `#3.3` Process form JSON template
#### `#3.3.1` Simple two field form
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

### `#3.4` Process form rules
There are also some rules that must be respected in order for the form to work correctly.

Here is a list of rules:  
1. The first form has no buttons
    - There is only one action available and that is __Submit__