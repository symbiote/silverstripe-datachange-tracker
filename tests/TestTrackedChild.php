<?php

namespace Symbiote\DataChange\Tests;

use SilverStripe\ORM\DataObject;

/**
 * 
 *
 * @author marcus
 */
class TestTrackedChild extends DataObject implements \SilverStripe\Dev\TestOnly
{
    private static $db = [
        'Title'     => 'Varchar',
    ];
}