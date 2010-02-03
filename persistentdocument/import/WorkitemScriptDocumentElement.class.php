<?php
/**
 * workflow_WorkitemScriptDocumentElement
 * @package modules.workflow.persistentdocument.import
 */
class workflow_WorkitemScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return workflow_persistentdocument_workitem
     */
    protected function initPersistentDocument()
    {
    	return workflow_WorkitemService::getInstance()->getNewDocumentInstance();
    }
}