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