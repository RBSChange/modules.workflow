<?php
class workflow_TransitionScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return workflow_persistentdocument_transition
     */
    protected function initPersistentDocument()
    {
    	return workflow_TransitionService::getInstance()->getNewDocumentInstance();
    }
}
