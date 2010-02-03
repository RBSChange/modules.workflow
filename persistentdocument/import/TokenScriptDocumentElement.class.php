<?php
/**
 * workflow_TokenScriptDocumentElement
 * @package modules.workflow.persistentdocument.import
 */
class workflow_TokenScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return workflow_persistentdocument_token
     */
    protected function initPersistentDocument()
    {
    	return workflow_TokenService::getInstance()->getNewDocumentInstance();
    }
}