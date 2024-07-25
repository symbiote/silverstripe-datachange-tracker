<?php

namespace Symbiote\DataChange\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\ManyManyList;
use Symbiote\DataChange\Service\DataChangeTrackService;
use Symbiote\DataChange\Model\TrackedManyManyList;

/**
 *
 *
 * @author marcus
 */
class DataChangeTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        TestTrackedObject::class,
        TestTrackedChild::class,
        TestTrackedUnderscoreObject::class,
        TestTrackedUnderscoreChild::class,
    ];

    public function testTrackChange()
    {
        $obj = TestTrackedObject::create(['Title' => 'Object title']);
        $obj->write();

        $this->getService()->resetChangeCache();

        $obj->Title = 'Changed title';
        $obj->write();

        $changes = $obj->getDataChangesList();

        $mapped = $changes->toArray();

        $this->assertEquals(2, count($mapped));

        $after = json_decode($mapped[0]->After, true);

        $this->assertEquals('Changed title', $after['Title']);
    }

    public function testManyManyChanges_TableWithNoUnderscores()
    {
        //
        // Setup data
        //
        $obj = TestTrackedObject::create(['Title' => 'Object title']);
        $obj->write();
        $this->getService()->resetChangeCache();

        $kid = TestTrackedChild::create(['Title' => 'kid object']);
        $kid->write();
        $this->getService()->resetChangeCache();

        $kid2 = TestTrackedChild::create(['Title' => 'kid2 object']);
        $kid2->write();
        $this->getService()->resetChangeCache();

        //
        // Test that we have overriden the injector config
        //
        $newInjectorConfig = [
            ManyManyList::class => [
                'class' => TrackedManyManyList::class,
                'properties' => [
                    'trackedRelationships' => [
                        "TestTrackedObject_Kids",
                    ]
                ]
            ]
        ];
        Injector::inst()->load($newInjectorConfig);

        // We want to check that $obj->Kids() is returning the injected TrackedManyManyList, not ManyManyList
        $this->assertEquals(TrackedManyManyList::class, $obj->Kids()::class);

        // We want to make sure the join table looks like how we expect.
        $this->assertEquals('TestTrackedObject_Kids', $obj->Kids()->getJoinTable());

        //
        // Test Many many changes
        //
        $obj->Kids()->add($kid);
        $this->getService()->resetChangeCache();

        $obj->Kids()->add($kid2);
        $this->getService()->resetChangeCache();

        $changes = $obj->getDataChangesList();

        $mapped = $changes->toArray();
        $this->assertEquals(
            3,
            count($mapped),
            "Mismatching change records. This means the Injector config is most likely broken. Make sure the 'trackedRelationships' logic is set correctly. (This statement was written in 2018-06-22)"
        );
        $this->assertEquals('Add "kid2 object" to Kids', $mapped[0]->ChangeType);
    }

    public function testAManyManyChanges_TableWithUnderscores()
    {
        //
        // Setup data
        //
        $obj = TestTrackedUnderscoreObject::create(['Title' => 'Underscore Object title']);
        $obj->write();
        $this->getService()->resetChangeCache();

        $kid = TestTrackedUnderscoreChild::create(['Title' => 'underscore kid object']);
        $kid->write();
        $this->getService()->resetChangeCache();

        $kid2 = TestTrackedUnderscoreChild::create(['Title' => 'underscore kid2 object']);
        $kid2->write();
        $this->getService()->resetChangeCache();

        //
        // Test that we have overriden the injector config
        //
        $newInjectorConfig = [
            ManyManyList::class => [
                'class' => TrackedManyManyList::class,
                'properties' => [
                    'trackedRelationships' => [
                        'Symbiote_DataChange_Tests_TestTrackedUnderscoreObject_Kids',
                    ]
                ]
            ]
        ];
        Injector::inst()->load($newInjectorConfig);

        // We want to check that $obj->Kids() is returning the injected TrackedManyManyList, not ManyManyList
        $this->assertEquals(TrackedManyManyList::class, $obj->Kids()::class);

        // We want to make sure the join table looks like how we expect.
        $this->assertEquals('Symbiote_DataChange_Tests_TestTrackedUnderscoreObject_Kids', $obj->Kids()->getJoinTable());

        //
        // Test Many many changes
        //
        $obj->Kids()->add($kid);
        $this->getService()->resetChangeCache();

        $obj->Kids()->add($kid2);
        $this->getService()->resetChangeCache();

        $changes = $obj->getDataChangesList();

        $mapped = $changes->toArray();
        $this->assertEquals(
            3,
            count($mapped),
            "Mismatching change records. This means the Injector config is most likely broken. Make sure the 'trackedRelationships' logic is set correctly. (This statement was written in 2018-06-22)"
        );
        $this->assertEquals('Add "underscore kid2 object" to Kids', $mapped[0]->ChangeType);
    }

    private function getService()
    {
        return Injector::inst()->get('DataChangeTrackService');
    }
}
