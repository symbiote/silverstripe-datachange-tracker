<?php

namespace Symbiote\DataChange\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\FieldType\DBText;
use Symbiote\DataChange\Extension\ChangeRecordable;

class TestTextJSONFieldObject extends DataObject implements TestOnly
{
    private static $table_name = 'TestTextJSONFieldObject';

    private static $db = [
        'TextFieldWithJSON'     => DBText::class,
    ];

    private static $extensions = [
        ChangeRecordable::class,
    ];

    /**
     * This is the getter that can cause the DataDifferencer to fall
     * over.
     *
     * This getter pattern was used in at least 1 internal Symbiote project.
     */
    public function getTextFieldWithJSON()
    {
        $value = $this->getField('TextFieldWithJSON');
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        return $value;
    }
}
