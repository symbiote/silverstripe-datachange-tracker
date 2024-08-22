<?php

namespace Symbiote\DataChange\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Versioned\DataDifferencer;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\Member;
use SilverStripe\Control\Director;

/**
 * Record a change to a dataobject; use this to track data changes of objects
 *
 * @author  marcus@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class DataChangeRecord extends DataObject
{
    private static $table_name = 'DataChangeRecord';
    private static $db = [
        'ChangeType' => 'Varchar',
        'ObjectTitle' => 'Varchar(255)',
        'Before' => 'Text',
        'After' => 'Text',
        'Stage' => 'Text',
        'CurrentEmail' => 'Text',
        'CurrentURL' => 'Varchar(255)',
        'Referer' => 'Varchar(255)',
        'RemoteIP' => 'Varchar(128)',
        'Agent' => 'Varchar(255)',
        'GetVars' => 'Text',
        'PostVars' => 'Text',
    ];
    private static $has_one = [
        'ChangedBy' => Member::class,
        'ChangeRecord' => DataObject::class
    ];
    private static $summary_fields    = [
        'ChangeType' => 'Change Type',
        'ChangeRecordClass' => 'Record Class',
        'ChangeRecordID' => 'Record ID',
        'ObjectTitle' => 'Record Title',
        'ChangedBy.Title' => 'User',
        'Created' => 'Modification Date'
    ];
    private static $searchable_fields = [
        'ChangeType',
        'ObjectTitle',
        'ChangeRecordClass',
        'ChangeRecordID'
    ];
    private static $default_sort      = 'ID DESC';

    /**
     * Should request variables be saved too?
     *
     * @var boolean
     */
    private static $save_request_vars      = false;
    private static $field_blacklist        = ['Password'];
    private static $request_vars_blacklist = ['url', 'SecurityID'];

    public function getCMSFields($params = null)
    {
        Requirements::css('symbiote/silverstripe-datachange-tracker: client/css/datachange-tracker.css');

        $fields = FieldList::create(
            ToggleCompositeField::create(
                'Details',
                'Details',
                [
                    ReadonlyField::create('ChangeType', 'Type of change'),
                    ReadonlyField::create('ChangeRecordClass', 'Record Class'),
                    ReadonlyField::create('ChangeRecordID', 'Record ID'),
                    ReadonlyField::create('ObjectTitle', 'Record Title'),
                    ReadonlyField::create('Created', 'Modification Date'),
                    ReadonlyField::create('Stage', 'Stage'),
                    ReadonlyField::create('User', 'User', $this->getMemberDetails()),
                    ReadonlyField::create('CurrentURL', 'URL'),
                    ReadonlyField::create('Referer', 'Referer'),
                    ReadonlyField::create('RemoteIP', 'Remote IP'),
                    ReadonlyField::create('Agent', 'Agent')
                ]
            )->setStartClosed(false)->addExtraClass('datachange-field'),
            ToggleCompositeField::create(
                'RawData',
                'Raw Data',
                [
                    ReadonlyField::create('Before'),
                    ReadonlyField::create('After'),
                    ReadonlyField::create('GetVars'),
                    ReadonlyField::create('PostVars')
                ]
            )->setStartClosed(false)->addExtraClass('datachange-field')
        );

        if (strlen($this->Before) && strlen($this->ChangeRecordClass) && class_exists($this->ChangeRecordClass)) {
            $before = Injector::inst()->create($this->ChangeRecordClass, $this->prepareForDataDifferencer($this->Before), true);
            $after  = Injector::inst()->create($this->ChangeRecordClass, $this->prepareForDataDifferencer($this->After), true);
            $diff   = DataDifferencer::create($before, $after);

            // The solr search service injector dependency causes issues with comparison, since it has public variables that are stored in an array.

            $diff->ignoreFields(['searchService']);
            $diffed   = $diff->diffedData();
            $diffText = '';

            $changedFields = [];
            foreach ($diffed->toMap() as $field => $prop) {
                if (is_object($prop)) {
                    continue;
                }
                if (is_array($prop)) {
                    $prop = json_encode($prop);
                }
                $changedFields[] = $readOnly        = \SilverStripe\Forms\ReadonlyField::create(
                    'ChangedField' . $field,
                    $field,
                    $prop
                );
                $readOnly->addExtraClass('datachange-field');
            }

            $fields->insertBefore(
                'RawData',
                ToggleCompositeField::create('FieldChanges', 'Changed Fields', $changedFields)
                    ->setStartClosed(false)
                    ->addExtraClass('datachange-field')
            );
        }

        // Flags fields that cannot be rendered with 'forTemplate'. This prevents bugs where
        // WorkflowService (of AdvancedWorkflow Module) and BlockManager (of Sheadawson/blocks module) get put
        // into a field and break the page.
        $fieldsToRemove = [];
        foreach ($fields->dataFields() as $field) {
            $value = $field->Value();
            if ($value && is_object($value)) {
                if ((method_exists($value, 'hasMethod') && !$value->hasMethod('forTemplate')) || !method_exists(
                    $value,
                    'forTemplate'
                )) {
                    $field->setValue('[Missing ' . $value::class . '::forTemplate]');
                }
            }
        }

        $fields = $fields->makeReadonly();

        return $fields;
    }

    /**
     * Track a change to a DataObject
     *
     * @return DataChangeRecord
     * */
    public function track(DataObject $changedObject, $type = 'Change')
    {
        $changes = $changedObject->getChangedFields(true, 2);
        if (count($changes)) {
            // remove any changes to ignored fields
            $ignored = $changedObject->hasMethod('getIgnoredFields') ? $changedObject->getIgnoredFields() : null;
            if ($ignored) {
                $changes = array_diff_key($changes, $ignored);
                foreach ($ignored as $ignore) {
                    if (isset($changes[$ignore])) {
                        unset($changes[$ignore]);
                    }
                }
            }
        }

        foreach (self::config()->field_blacklist as $key) {
            if (isset($changes[$key])) {
                unset($changes[$key]);
            }
        }

        if ((empty($changes) && $type == 'Change')) {
            return;
        }

        if ($type === 'Delete' && Versioned::get_reading_mode() === 'Stage.Live') {
            $type = 'Delete from Live';
        }

        $this->ChangeType = $type;

        $this->ChangeRecordClass = $changedObject->ClassName;
        $this->ChangeRecordID    = $changedObject->ID;
        // @TODO this will cause issue for objects without titles
        $this->ObjectTitle       = $changedObject->Title;
        $this->Stage             = Versioned::get_reading_mode();

        $before = [];
        $after  = [];

        if ($type != 'Change' && $type != 'New') { // If we are (un)publishing we want to store the entire object
            $before = ($type === 'Unpublish') ? $changedObject->toMap() : null;
            $after  = ($type === 'Publish') ? $changedObject->toMap() : null;
        } else { // Else we're tracking the changes to the object
            foreach ($changes as $field => $change) {
                if ($field == 'SecurityID') {
                    continue;
                }
                $before[$field] = $change['before'];
                $after[$field]  = $change['after'];
            }
        }

        if ($this->Before && $this->Before !== 'null' && is_array($before)) {
            //merge the old array last to keep it's value as we want keep the earliest version of each field
            $this->Before = json_encode(array_replace(json_decode($this->Before, true), $before));
        } else {
            $this->Before = json_encode($before);
        }
        if ($this->After && $this->After !== 'null' && is_array($after)) {
            //merge the new array last to keep it's value as we want the newest version of each field
            $this->After = json_encode(array_replace($after, json_decode($this->After, true)));
        } else {
            $this->After = json_encode($after);
        }

        if (self::config()->save_request_vars) {
            foreach (self::config()->request_vars_blacklist as $key) {
                unset($_GET[$key]);
                unset($_POST[$key]);
            }

            $this->GetVars  = isset($_GET) ? json_encode($_GET) : null;
            $this->PostVars = isset($_POST) ? json_encode($_POST) : null;
        }

        if ($member = Security::getCurrentUser()) {
            $this->ChangedByID = $member->ID;
            $this->CurrentEmail = $member->Email;
        }

        if (isset($_SERVER['SERVER_NAME'])) {
            $protocol = 'http';
            $protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" ? 'https://' : 'http://';
            $port = $_SERVER['SERVER_PORT'] ?? '80';

            $this->CurrentURL = $protocol . $_SERVER["SERVER_NAME"] . ":" . $port . $_SERVER["REQUEST_URI"];
        } elseif (Director::is_cli()) {
            $this->CurrentURL = 'CLI';
        } else {
            $this->CurrentURL = 'Could not determine current URL';
        }

        $this->RemoteIP = $_SERVER['REMOTE_ADDR'] ?? (Director::is_cli() ? 'CLI' : 'Unknown remote addr');
        $this->Referer  = $_SERVER['HTTP_REFERER'] ?? '';
        $this->Agent    = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $this->write();
        return $this;
    }

    /**
     * @return boolean
     * */
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * @return string
     * */
    public function getTitle()
    {
        return $this->ChangeRecordClass . ' #' . $this->ChangeRecordID;
    }

    /**
     * Return a description/summary of the user
     *
     * @return string
     * */
    public function getMemberDetails()
    {
        if ($user = $this->ChangedBy()) {
            $name = $user->getTitle();
            if ($user->Email) {
                $name .= " <$user->Email>";
            }
            return $name;
        }
    }

    private function prepareForDataDifferencer($jsonData)
    {
        // NOTE(Jake): 2018-06-21
        //
        // Data Differencer cannot handle arrays within an array,
        //
        // So JSON data that comes from MultiValueField / Text DB fields
        // causes errors to be thrown.
        //
        // So solve this, we simply only decode to a depth of 1. (rather than the 512 default)
        //
        $resultJsonData = json_decode((string) $jsonData, true, 1);
        return $resultJsonData;
    }
}
