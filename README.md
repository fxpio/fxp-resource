Fxp Resource
============

[![Latest Version](https://img.shields.io/packagist/v/fxp/resource.svg)](https://packagist.org/packages/fxp/resource)
[![Build Status](https://img.shields.io/travis/fxpio/fxp-resource/master.svg)](https://travis-ci.org/fxpio/fxp-resource)
[![Coverage Status](https://img.shields.io/coveralls/fxpio/fxp-resource/master.svg)](https://coveralls.io/r/fxpio/fxp-resource?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/fxpio/fxp-resource/master.svg)](https://scrutinizer-ci.com/g/fxpio/fxp-resource?branch=master)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/4a011831-ccae-417c-9789-49476cdde93e.svg)](https://insight.sensiolabs.com/projects/4a011831-ccae-417c-9789-49476cdde93e)

The Fxp Resource is a resource management layer for doctrine. This library has been
designed to facilitate the creation of a Batch API for processing a list of resources<sup>1</sup>
(ex. external data loader).

However, it is entirely possible to build an API Bulk above this library.

It allows to easily perform actions on Doctrine using the best practices automatically according
to selected options (flush for each resource or for all resources, but also skip errors of the
invalid resources), whether for a resource or set of resources.

Features include:

- Resource Domain Manager for get a resource domain for an doctrine resource
- Resource Domain for each doctrine resource for easy management:
  - generate new instance of resource with default value configured by Fxp Default Value
  - create one resource with validation (for object or Form instance)
  - create a list of resources with validation for each resource (for object or Form instance)
  - update one resource with validation (for object or Form instance)
  - update a list of resources with validation for each resource (for object or Form instance)
  - upsert one resource with validation (create or update for object or Form instance)
  - upsert a list of resources with validation for each resource (create or update for object or Form instance)
  - delete one resource with soft delete or hard delete for compatible resources
  - delete a list of resources with soft delete or hard delete for compatible resources
  - undelete one resource for compatible resources with soft delete
  - undelete a list of resources for compatible resources with soft delete
- Each resource domain allow:
  - to have the possibility to do an transaction with rollback for each resource of the list or for all resources in only one time
  - to have the possibility to skip the errors of an resource, and continue to run the rest of the list (compatible only with the transaction for each resource)
  - to return the list of resources with the status of the action (created, updated, error ...) on each resource of the list
- Request content converter:
  - JSON converter
- Form handler to work with Symfony Form

> **Note:**
> <sup>1</sup> A resource is an doctrine entity or doctrine document

Documentation
-------------

The bulk of the documentation is stored in the `Resources/doc/index.md`
file in this library:

[Read the Documentation](Resources/doc/index.md)

Installation
------------

All the installation instructions are located in [documentation](Resources/doc/index.md).

License
-------

This library is under the MIT license. See the complete license in the library:

[LICENSE](LICENSE)

About
-----

Fxp Resource is a [François Pluchino](https://github.com/francoispluchino) initiative.
See also the list of [contributors](https://github.com/fxpio/fxp-resource/graphs/contributors).

Reporting an issue or a feature request
---------------------------------------

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/fxpio/fxp-resource/issues).
