<?php

namespace Symbiote\DataChange\Tests;

use \SilverStripe\Core\Injector\Injector;

/**
 * 
 *
 * @author marcus
 */
class DataChangeTest extends \SilverStripe\Dev\SapphireTest
{
    protected static $extra_dataobjects = [
        'Symbiote\DataChange\Tests\TestTrackedObject',
        'Symbiote\DataChange\Tests\TestTrackedChild',
    ];

    public function testTrackChange()
    {
        $obj = TestTrackedObject::create(['Title' => 'Object title']);
        $obj->write();

        singleton('DataChangeTrackService')->resetChangeCache();

        $obj->Title = 'Changed title';
        $obj->write();

        $changes = $obj->getDataChangesList();

        $mapped = $changes->toArray();

        $this->assertEquals(2, count($mapped));

        $after = json_decode($mapped[0]->After, true);

        $this->assertEquals('Changed title', $after['Title']);
    }

    public function testManyManyChanges() {
        $injectorConfig = \SilverStripe\Core\Config\Config::inst()->get(Injector::class);

        $injectorConfig['SilverStripe\ORM\ManyManyList'] = [
            'class' => 'Symbiote\DataChange\Model\TrackedManyManyList',
            'properties' => [
                'trackedRelationships' => [
                    "Symbiote_DataChange_Tests_TestTrackedObject_Kids",
                ]
            ]
        ];

        \SilverStripe\Core\Config\Config::modify()->set(Injector::class, 'SilverStripe\ORM\ManyManyList', $injectorConfig['SilverStripe\ORM\ManyManyList']);


        $obj = TestTrackedObject::create(['Title' => 'Object title']);
        $obj->write();

        singleton('DataChangeTrackService')->resetChangeCache();

        $kid = TestTrackedChild::create(['Title' => 'kid object']);
        $kid->write();

        singleton('DataChangeTrackService')->resetChangeCache();
        $kid2 = TestTrackedChild::create(['Title' => 'kid2 object']);
        $kid2->write();

        singleton('DataChangeTrackService')->resetChangeCache();

        $obj->Kids()->add($kid);
        singleton('DataChangeTrackService')->resetChangeCache();

        $obj->Kids()->add($kid2);
        singleton('DataChangeTrackService')->resetChangeCache();
        
        $changes = $obj->getDataChangesList();

        $mapped = $changes->toArray();
        $this->assertEquals(3, count($mapped));
        $this->assertEquals('Add "kid2 object" to DataChange', $mapped[0]->ChangeType);
    }
}