# SkyDocu
SkyDocu is a web DMS that has been created to be run in a cloud. It has a superadministration where the superadministrator can manage containers.
Containers are instances of DMS applications for customers. Containers cannot access data of other containers.

Latest release is always in the `main` branch and a package is also available in the Releases section. However if you are interested in new functionalities, you can switch to the `-dev` branch with prefix of the version - e.g. `1.4-dev` is the development version of `1.4`. It is an unstable version and there's no guarantee it will work on a new installation.

For more information about versions and the changelog, check out the [CHANGELOG](CHANGELOG.md).

You can also check out the [PROGRAMMING DOCUMENTATION](PROGRAMMING_DOCUMENTATION.md). Unfortunately it still not complete and it is still being updated with up-to-date information.

## Tech stack
The web application is written purely in PHP with a few JS scripts. These JS scripts are mostly used for AJAX and other dynamic behavior functions.

For more information about the tech stack, check out the [PROGRAMMING DOCUMENTATION](PROGRAMMING_DOCUMENTATION.md).