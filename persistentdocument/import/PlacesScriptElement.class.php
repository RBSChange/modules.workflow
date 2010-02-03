<?php
class workflow_PlacesScriptElement extends  import_ScriptBaseElement
{
    public function endProcess()
    {
        $children = $this->script->getChildren($this);      
        if (count($children))
        {
        	$workflow = $this->getWorkflow();
			foreach ($children as $element) 
			{
				if ($element instanceof workflow_PlaceScriptDocumentElement)
				{
					$workflow->addPlaces($element->getPersistentDocument());
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