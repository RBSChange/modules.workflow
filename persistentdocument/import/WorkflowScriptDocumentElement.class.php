<?php
class workflow_WorkflowScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return workflow_persistentdocument_workflow
     */
    protected function initPersistentDocument()
    {
    	return workflow_WorkflowService::getInstance()->getNewDocumentInstance();
    }
    
    public function endProcess ()
    {
        $workflow = $this->getPersistentDocument();
        if ($workflow->getPublicationstatus() == 'DRAFT')
        {
            $workflow->getDocumentService()->activate($workflow->getId());
        }
        
        $workflowDesignerService = workflow_WorkflowDesignerService::getInstance();
		$workflowDesignerService->validateWorkflowDefinitionById($workflow->getId());
    }
}