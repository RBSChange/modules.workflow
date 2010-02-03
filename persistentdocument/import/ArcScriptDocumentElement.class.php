<?php
class workflow_ArcScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return workflow_persistentdocument_arc
	 */
	protected function initPersistentDocument()
	{
		return workflow_ArcService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return array
	 */
	protected function getDocumentProperties()
	{
		$attributes = parent::getDocumentProperties();
		
		if (isset($attributes['placerefid']))
		{
			$placeRefId = $attributes['placerefid'];
			unset($attributes['placerefid']);
			
			$placeScript = $this->script->getElementById($placeRefId);
			if ($placeScript instanceof workflow_PlaceScriptDocumentElement)
			{
				$attributes['place'] = $placeScript->getPersistentDocument();
			}
		}
		
		if (isset($attributes['transitionrefid']))
		{
			$transitionRefId = $attributes['transitionrefid'];
			unset($attributes['transitionrefid']);
			$transitionScript = $this->script->getElementById($transitionRefId);
			if ($transitionScript instanceof workflow_TransitionScriptDocumentElement) 
			{
				$attributes['transition'] = $transitionScript->getPersistentDocument();
			}
		}
		
		return $attributes;
	}
}
