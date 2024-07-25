<?php

namespace Symbiote\DataChange\Tests;

use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Control\Controller;
use Symbiote\DataChange\Admin\DataChangeAdmin;

class DataChangeCMSTest extends FunctionalTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        TestTextJSONFieldObject::class,
    ];

    public function testCMSFieldsWithJSONData()
    {
        // Create test data
        $record = new TestTextJSONFieldObject();
        $record->TextFieldWithJSON = json_encode([
            'The Pixies' => [
                'Bossanova' => [
                    'The Happening' => [
                        'My head was feeling scared',
                        'but my heart was feeling free',
                    ]
                ],
            ]
        ]);
        $record->write();
        $record->TextFieldWithJSON = json_encode([
            'Radiohead' => [
                'A Moonshaped Pool' => [
                    'Present Tense' => [
                        'Keep it light and',
                        'Keep it moving',
                        'I am doing',
                        'No harm',
                    ]
                ],
            ]
        ]);
        $record->write();

        // Get the data change tracker record that was written in 'TestTextJSONFieldObject's onAfterWrite()
        $dataChangeTrackRecordIds = $record->getDataChangesList()->column('ID');
        $this->assertEquals(2, count($dataChangeTrackRecordIds));

        // View in the CMS.
        $this->logInWithPermission('ADMIN');
        $dataChangeTrackEditID = $dataChangeTrackRecordIds[0];
        $editLink = 'admin/datachanges/Symbiote-DataChange-Model-DataChangeRecord/EditForm/field/Symbiote-DataChange-Model-DataChangeRecord/item/'.$dataChangeTrackEditID.'/edit';

        // NOTE(Jake): 2018-06-25
        //
        // If the test fails, you will get something like:
        // - nl2br() expects parameter 1 to be string, array given
        //
        // This is because the DataDifferencer can't work wtih a 'Text' field that returns an array.
        // ie. `TestTextJSONFieldObject` custom getter "getTextFieldWithJSON"
        //
        $response = $this->get($editLink);
        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertTrue(
            true,
            str_contains($body, 'Get Vars')
        );
        $this->assertTrue(
            true,
            str_contains($body, 'Post Vars')
        );
    }
}
