<?php

namespace Symbiote\DataChange\Extension;

use Symbiote\DataChange\Model\DataChangeRecord;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\GridField\GridField;

/**
 * Add to Pages you want changes recorded for
 *
 * @author  stephen@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class SiteTreeChangeRecordable extends ChangeRecordable
{

    public function onAfterPublish(&$original)
    {
        $this->dataChangeTrackService->track($this->owner, 'Publish');
    }

    public function onAfterUnpublish()
    {
        $this->dataChangeTrackService->track($this->owner, 'Unpublish');
    }

    public function updateCMSFields(FieldList $fields)
    {
        if (Permission::check('CMS_ACCESS_DataChangeAdmin')) {
            //Get all data changes relating to this page filter them by publish/unpublish
            $dataChanges = DataChangeRecord::get()->filter([
                    'ChangeRecordID' => $this->owner->ID,
                    'ChangeRecordClass' => $this->owner->ClassName
                ])->exclude('ChangeType', 'Change');

            //create a gridfield out of them
            $gridFieldConfig = GridFieldConfig_RecordViewer::create();
            $publishedGrid   = new GridField('PublishStates', 'Published States', $dataChanges, $gridFieldConfig);
            $dataColumns     = $publishedGrid->getConfig()->getComponentByType('SilverStripe\Forms\GridField\GridFieldDataColumns');
            $dataColumns->setDisplayFields(
                array('ChangeType' => 'Change Type',
                    'ObjectTitle' => 'Page Title',
                    'ChangedBy.Title' => 'User',
                    'Created' => 'Modification Date',
                )
            );

            //linking through to the datachanges modeladmin

            $fields->addFieldsToTab('Root.PublishedState', $publishedGrid);
            return $fields;
        }
    }
}
