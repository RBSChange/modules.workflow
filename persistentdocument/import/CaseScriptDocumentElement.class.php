<?php
/**
 * workflow_CaseScriptDocumentElement
 * @package modules.workflow.persistentdocument.import
 */
class workflow_CaseScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return workflow_persistentdocument_case
     */
    protected function initPersistentDocument()
    {
    	return workflow_CaseService::getInstance()->getNewDocumentInstance();
    }
}