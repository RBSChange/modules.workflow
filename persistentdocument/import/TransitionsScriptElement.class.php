<?php
class workflow_TransitionsScriptElement extends  import_ScriptBaseElement
{
    public function endProcess()
    {
        $children = $this->script->getChildren($this);      
        if (count($children))
        {
        	$workflow = $this->getWorkflow();
			foreach ($children as $element) 
			{
				if ($element instanceof workflow_TransitionScriptDocumentElement)
				{
					$workflow->addTransitions($element->getPersistentDocument());
				}
			}   
        }
    }
    
    /**
     * @return workflow_persistentdocument_workflow
     */
    public function getWorkflow()
    {
    	$parent = $this->getParent();
    	return $parent->getPersistentDocument();
    }
}