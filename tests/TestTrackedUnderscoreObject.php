<?php

namespace Symbiote\DataChange\Tests;

use SilverStripe\ORM\DataObject;
use Symbiote\DataChange\Extension\ChangeRecordable;
use SilverStripe\Dev\TestOnly;

/**
 *
 *
 * @author marcus
 */
class TestTrackedUnderscoreObject extends DataObject implements TestOnly
{
    private static $table_name = 'Symbiote_DataChange_Tests_TestTrackedUnderscoreObject';

    private static $db = [
        'Title'     => 'Varchar',
    ];

    private static $many_many = [
        'Kids'      => TestTrackedUnderscoreChild::class,
    ];

    private static $extensions = [
        ChangeRecordable::class,
    ];
}
