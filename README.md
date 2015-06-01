# SilverStripe Data Change Tracker

## Requirements

* SilverStripe 3

## Maintainers

* marcus@silverstripe.com.au
* shea@silverstripe.com.au

## Description

Record and track changes and deletes of any dataobjects. View changes/diffs in model admin.

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