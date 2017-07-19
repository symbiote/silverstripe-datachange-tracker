<?php

if (class_exists('AbstractQueuedJob')) {
    
/**
 * A scheduled regular prune of _old_ data change records
 * @author marcus
 */
class PruneChangesBeforeJob extends AbstractQueuedJob
{
    
    public function __construct($priorTo = null)
    {
        if ($priorTo) {
            $ts = strtotime($priorTo);
            if ($ts <= 0) {
                $ts = time() - 90*86400;
            }
            $this->priorTo = $priorTo;
            $this->pruneBefore = date('Y-m-d 00:00:00', $ts);
            $this->totalSteps = DataChangeRecord::get()->filter('Created:LessThan', $this->pruneBefore)->count();
        }
    }
    
    public function getSignature()
    {
        return md5($this->pruneBefore);
    }
    
    public function getTitle()
    {
        return "Prune data change track entries before " . $this->pruneBefore;
    }
    
    public function setup()
    {
        $this->pruneIds = DataChangeRecord::get()->filter('Created:LessThan', $this->pruneBefore)->column();
        $this->totalSteps = count($this->pruneIds);
    }
    
    public function process() {
        $items = DataChangeRecord::get()->filter('Created:LessThan', $this->pruneBefore);
		$max = $items->max('ID');
        
        $query = new SQLDelete('DataChangeRecord', '"ID" < \'' . $max . '\'');
        $query->execute();
        
        $job = new PruneChangesBeforeJob($this->priorTo);
        
        $next = date('Y-m-d 03:00:00', strtotime('tomorrow'));
        
        $this->currentStep = $this->totalSteps;
        $this->isComplete = true;
        
        singleton('QueuedJobService')->queueJob($job, $next);
    }
}

}
