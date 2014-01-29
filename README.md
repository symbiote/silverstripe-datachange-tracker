# SilverStripe Data Change Tracker

## Requirements

* SilverStripe 3

## Maintainers

* marcus@silverstripe.com.au
* shea@silverstripe.com.au

## Description

Record and track changes to any dataobjects. View chages/diffs in model admin.

## Installation

Install via composer, run dev/build
	
	composer require silverstripe-australia/datachange-tracker

## Usage

Add the ChangeRecordable extension to the dataobjects you wish to track

```
SiteTree:
  extensions:
    - ChangeRecordable
```

## Ignoring fields

In some cases it may not be desirable to track changes to all fields of an object. You can define ignored fields in your yml config like so:

```
ChangeRecordable:
  ignored_fields:
    NameOfObjectClass:
      - NameOfField
```