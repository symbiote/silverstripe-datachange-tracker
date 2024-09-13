<?php

namespace Symbiote\DataChange\Service;

use Symbiote\DataChange\Model\DataChangeRecord;

use SilverStripe\ORM\DataObject;

/**
 * @author Stephen McMahon <stephen@symbiote.com.au>
 */
class DataChangeTrackService implements \Stringable
{

    protected $dcr_cache = [];

    public $disabled = false;

    public function track(DataObject $object, $type = 'Change')
    {

        if ($this->disabled) {
            return;
        }

        if (!isset($this->dcr_cache["{$object->ID}-{$object->Classname}-$type"])) {
            $this->dcr_cache["{$object->ID}-{$object->Classname}"] = DataChangeRecord::create();
        }

        $this->dcr_cache["{$object->ID}-{$object->Classname}"]->track($object, $type);
    }

    public function resetChangeCache()
    {
        $this->dcr_cache = [];
    }

    public function __toString(): string
    {
        return '';
    }
}
