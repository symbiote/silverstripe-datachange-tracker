# Data Change Tracker

[![Build Status](https://travis-ci.org/symbiote/silverstripe-datachange-tracker.svg?branch=master)](https://travis-ci.org/symbiote/silverstripe-datachange-tracker)
[![Latest Stable Version](https://poser.pugx.org/symbiote/silverstripe-datachange-tracker/version.svg)](https://github.com/symbiote/silverstripe-datachange-tracker/releases)
[![Latest Unstable Version](https://poser.pugx.org/symbiote/silverstripe-datachange-tracker/v/unstable.svg)](https://packagist.org/packages/symbiote/silverstripe-datachange-tracker)
[![Total Downloads](https://poser.pugx.org/symbiote/silverstripe-datachange-tracker/downloads.svg)](https://packagist.org/packages/symbiote/silverstripe-datachange-tracker)
[![License](https://poser.pugx.org/symbiote/silverstripe-datachange-tracker/license.svg)](https://github.com/symbiote/silverstripe-datachange-tracker/blob/master/LICENSE.md)

Compatible with SilverStripe 4

## Maintainers

* marcus@symbiote.com.au

## Description

Record and track changes and deletes of any dataobjects. View changes/diffs in model admin.

Additionally, track add/remove of items in many\_many relationships

## Installation

Composer Install

```
composer require symbiote/silverstripe-datachange-tracker:~5.0
```

## Requirements

* SilverStripe 4

## Documentation 

* [Quick Start](docs/en/quick-start.md)
* [License](LICENSE.md)
* [Contributing](CONTRIBUTING.md)

## Version details

*3.2.0*

* Added pruning of data via queuedjobs

*3.0.0*

* Removed static DataChangeRecord::track() method

