# Data Change Tracker


## Maintainers

* marcus@symbiote.com.au

## Description

Record and track changes and deletes of any dataobjects. View changes/diffs in model admin.

Additionally, track add/remove of items in many\_many relationships

## Installation

## Composer Install

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

