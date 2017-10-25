<?php

namespace Symbiote\DataChange\Tests;

use SilverStripe\ORM\DataObject;

/**
 * 
 *
 * @author marcus
 */
class TestTrackedObject extends DataObject implements \SilverStripe\Dev\TestOnly
{
    private static $db = [
        'Title'     => 'Varchar',
    ];

    private static $many_many = [
        'Kids'      => 'Symbiote\DataChange\Tests\TestTrackedChild',
    ];

    private static $extensions = [
        'Symbiote\DataChange\Extension\ChangeRecordable'
    ];
}