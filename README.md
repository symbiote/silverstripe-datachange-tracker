# SilverStripe Data Change Tracker

## Requirements

* SilverStripe 3

## Maintainers

* marcus@silverstripe.com.au

## Description

Record and track changes and deletes of any dataobjects. View changes/diffs in model admin.

Additionally, track add/remove of items in many\_many relationships

## Installation

Install via composer, run dev/build
	
	composer require silverstripe-australia/datachange-tracker

## Usage

Add the ChangeRecordable extension to the dataobjects you wish to track

```
MyDataObject:
  extensions:
    - ChangeRecordable
```

If you are applying the extension to the SiteTree, use the SiteTreeChangeRecordable extension to record publish/unpublish actions

To track changes to many\_many relationships, you must use a custom 
ManyManyRelationship class, as well as indicate which relationships need 
tracking. This can be directly configured via the Injector

For example

```
Injector:
  ManyManyList:
    class: TrackedManyManyList
    properties:
      trackedRelationships:
        - Page_Regions

```

will track the "Regions" relationship defined on the Page class;

```
private static $many_many = array(
	'Regions'	=> 'SomeObject',
);

```



## Capturing URL parameters

Set the `save_request_vars` option to 1, and GET and POST vars will be recorded too. 

```
DataChangeRecord:
  save_request_vars: 1

```


## Ignoring fields

In some cases it may not be desirable to track changes to all fields of an object. You can define ignored fields in your yml config like so:

```
ChangeRecordable:
  ignored_fields:
    NameOfObjectClass:
      - NameOfField
```

Or, for the same field name across all objects

```
DataChangeRecord:
  field_blacklist:
    - Password
    - Email

```

Also, you may wish to blacklist some request variables from being stored 

```
DataChangeRecord:
  request_vars_blacklist:
    - url
    - SecurityID

```

## Significant Change tracking

Sometimes reporting changes to certain fields to CMS users is desirable (e.g seeing the last time a field was updated).
This is handled by `SignificantChangeRecordable`, which looks for a list of `significant_fields`.

Example:

```
TeamMember:
  significant_fields:
    - 'Name'
    - 'Address'
    - 'OfficeNumber'
    - 'Position'
    - 'Mobile'
  extensions:
    - 'ChangeRecordable'
    - 'SignificantChangeRecordable'
```

## Pruning old data

Over time, the data recorded will become overwhelming in size. May not be a problem for you, but if it is
you can regularly prune it to retain just (N) months of data at a time. Simply create the `PruneChangesBeforeJob`
from the QueuedJob admin section of the CMS, using a constructor param of something like "-6 months". 

The job will restart itself to run each night, to consistently remove the past 6 months of data. 

## Version details

*3.2.0*

* Added pruning of data via queuedjobs

*3.0.0*

* Removed static DataChangeRecord::track() method

