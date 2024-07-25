<?php

namespace Symbiote\DataChange\Extension;

use DateTime;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Config\Config;

/**
 * Add to classes you want to track specfic changes on
 *
 * @author  stephen@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class SignificantChangeRecordable extends DataExtension
{

    private static $ignored_fields = [];

    private static $significant_fields = [];

    private static $db = ['LastSignificantChange' => 'DBDatetime', 'ChangeDescription' => 'Text'];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('LastSignificantChange');
        $fields->removeByName('ChangeDescription');
        if ($this->owner->LastSignificantChange !== null) {
            $dateTime = new DateTime($this->owner->LastSignificantChange);
            //Put these fields on the top of the First Tab's form
            $fields->first()->Tabs()->first()->getChildren()->unshift(
                LiteralField::create(
                    "infoLastSignificantChange",
                    "<strong>Last Significant change was at: "
                    . "{$dateTime->Format('d/m/Y H:i')}</strong>"
                )->setAllowHTML(true)
            );
            $fields->insertAfter(
                CheckboxField::create(
                    "isSignificantChange",
                    "CLEAR Last Significant change: {$dateTime->Format('d/m/Y H:i')}"
                )->setDescription(
                    'Check and save this Record again to clear the Last Significant change date.'
                )->setValue(false),
                'infoLastSignificantChange'
            );
            $fields->insertAfter(
                TextField::create('ChangeDescription', 'Description of Changes')
                ->setDescription('This is an automatically generated list of changes to important fields.'),
                'isSignificantChange'
            );
        }
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        // Load the significant_fields and check to see if they have changed if they have record the current DateTime
        $significant = Config::inst()->get($this->owner->Classname, 'significant_fields');

        $isSignicantChange = $this->owner->isSignificantChange;

        if (isset($significant) && !$isSignicantChange) {
            $significant = array_combine($significant, $significant);

            //If the owner object or an extension of it implements getSignificantChange call it instead of testing here
            if ($this->owner->hasMethod('getSignificantChange') && $this->owner->getSignificantChange()) {
                //Set LastSignificantChange to now
                $this->owner->LastSignificantChange = date(DateTime::ATOM);
            } else {
                $changes = $this->owner->getChangedFields(true, 2);
                //A simple interesect of the keys gives us whether a change has occurred
                if (count($changes) && count(array_intersect_key($changes, $significant))) {
                    //Set LastSignificantChange to now
                    $this->owner->LastSignificantChange = date(DateTime::ATOM);
                }
            }
            //If we don't have any significant changes leave the field alone as a previous edit may have been
            //significant.
        } else {
            if ($this->owner->isInDB()) {
                $this->owner->LastSignificantChange = null;
            }
        }
    }
}
