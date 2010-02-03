<?php
class workflow_PlaceScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return workflow_persistentdocument_place
     */
    protected function initPersistentDocument()
    {
    	return workflow_PlaceService::getInstance()->getNewDocumentInstance();
    }
    
	protected function getDocumentType()
    {
    	// implemented for workflow_StartPlaceScriptDocumentElement
    	// and workflow_EndPlaceScriptDocumentElement sub-classes
		return "modules_workflow/place";
    }
}

class workflow_StartPlaceScriptDocumentElement extends workflow_PlaceScriptDocumentElement
{
    /**
     * @return workflow_persistentdocument_place
     */
    protected function initPersistentDocument()
    {
    	$workflow = $this->getParent()->getWorkflow();
    	return workflow_WorkflowService::getInstance()->getStartPlace($workflow);
    }
    
    
}

class workflow_EndPlaceScriptDocumentElement extends workflow_PlaceScriptDocumentElement
{
    /**
     * @return workflow_persistentdocument_place
     */
    protected function initPersistentDocument()
    {
    	$workflow = $this->getParent()->getWorkflow();
    	return workflow_WorkflowService::getInstance()->getEndPlace($workflow);
    }
}