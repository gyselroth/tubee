# Getting started

You need two things to get started, a server (usually you want a hosted server) and tubectl. 
tubectl is the console client for Linux, Windows and OS X.

The tubee server works with various different resource types. Each of those resources must be created manually to get a working setup.

Resorce types:

| Resource      | Description  |
| ------------- |:-------------:|
| Namespace | Resources like collections must be part of a single namespace.  |
| Collection | A collection is group of similar data objects. Each collection holds data objects and is part of a namespace.|
| Endpoint | An endpoint represents a remote server for proxying, import from, or export to. |
| DataObject  | An actual object which must be part of a collection. |
| EndpointObject  | Besides data objects there are also endpoint objects. The diference is that an endpoint object represents the state of an object on an endpoint. |
| Workflow   | A workflow defines how and what data should be synchronized between endpoints and collections. A worklfow is always attached to an endpoint.|
| Secret  | Holds sensible data which can be injected into other resources. Usually secrets injected into endpoint resources. |
| User  | A simple user with password authentication. (You may also use OpenID-connect or LDAP auth adapter instead local user resources) |
| AccessRole  | Defines an access role which can be used to gain access. Authenticated users are are part of an access-rule. |
| AccessRule  | Create access rules (RBAC) based on HTTP requests. |
| Job | A jobs defines what endpoints (or whole mandators or collections) should be synchronized and at what time/interval. |
| Process | A process represents a single execution of a job |
| Log | Each process/job will create log resources which can be requested for each |
