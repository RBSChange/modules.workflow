<?php
/**
 * @package modules.workflow
 * @method workflow_WorkflowService getInstance()
 */
class workflow_WorkflowService extends f_persistentdocument_DocumentService
{
	/**
	 * @return workflow_persistentdocument_workflow
	 */
	public function getNewDocumentInstance()
	{
		$workflow = $this->getNewDocumentInstanceByModelName('modules_workflow/workflow');
		
		// Automatically add the start place.
		$startPlace = workflow_PlaceService::getInstance()->getNewDocumentInstance();
		$startPlace->setPlacetype(WorkflowHelper::PLACE_TYPE_START);
		$startPlace->setLabel(f_Locale::translate('&modules.workflow.bo.general.AutoGenerated-StartPlace;'));
		$workflow->addPlaces($startPlace);

		// Automatically add the end place.
		$endPlace = workflow_PlaceService::getInstance()->getNewDocumentInstance();
		$endPlace->setPlacetype(WorkflowHelper::PLACE_TYPE_END);
		$endPlace->setLabel(f_Locale::translate('&modules.workflow.bo.general.AutoGenerated-EndPlace;'));
		$workflow->addPlaces($endPlace);

		return $workflow;
	}

	/**
	 * Create a query based on 'modules_workflow/workflow' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_workflow/workflow');
	}

	/**
	 * @param workflow_persistentdocument_workflow $document
	 * @return void
	 */
	protected function preDelete($document)
	{
		// Delete all cases associated to the deleted workflow.
		$query = $this->getPersistentProvider()->createQuery('modules_workflow/case');
		$query->createCriteria('workflow')->add(Restrictions::eq('id', $document->getId()));
		$casesArray = $this->getPersistentProvider()->find($query);
		foreach ($casesArray as $case)
		{
			$case->delete();
		}
	}
	
	/**
	 * @param workflow_persistentdocument_workflow $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		// Validate this workflow document.
		$task = $document->getStarttaskid();
		if (empty($task))
		{
			$this->setActivePublicationStatusInfo($document, 'm.workflow.bo.general.error-taskandlabelneeded');
			return false;
		}

		// Check if there is a start place and a end place.
		if ($this->getStartPlace($document) === null || $this->getEndPlace($document) === null)
		{
			$this->setActivePublicationStatusInfo($document, 'm.workflow.bo.general.error-startandendplacesneeded');
			return false;
		}

		// Validate arcs.
		$arcsArray = $document->getArcsArray();
		foreach ($arcsArray as $arc)
		{
			if (!$arc->getDocumentService()->validatePath($arc))
			{
				return false;
			}
		}

		// Validate places.
		$placeArray = $document->getPlacesArray();
		foreach ($placeArray as $place)
		{
			if (!$place->getDocumentService()->validatePath($place))
			{
				return false;
			}
		}

		// Validate transitions.
		$transitionArray = $document->getTransitionsArray();
		foreach ($transitionArray as $transition)
		{
			if (!$transition->getDocumentService()->validatePath($transition))
			{
				return false;
			}
		}

		// Test if there is no orther active workflow for this task on the publication interval.
		if ($this->hasOtherActiveWorkflowDefinitions($document))
		{
			$this->setActivePublicationStatusInfo($document, '&modules.workflow.bo.general.Error-AnotherValidWorkflowOnInterval;');
			return false;
		}

		return parent::isPublishable($document);
	}

	/**
	 * Get the workflow start place.
	 * @param workflow_persistentdocument_workflow $workflow
	 * @return workflow_persistentdocument_place
	 */
	public function getStartPlace($workflow)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : start for the workflow ' . $workflow->getId());
		}
		$placesArray = $workflow->getPlacesArray();
		foreach ($placesArray as $place)
		{
			if($place->getPlacetype() == WorkflowHelper::PLACE_TYPE_START)
			{
				return $place;
			}
		}
		return null;
	}

	/**
	 * Get the workflow end place.
	 * @param workflow_persistentdocument_workflow $workflow
	 * @return workflow_persistentdocument_place
	 */
	public function getEndPlace($workflow)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : start for the workflow ' . $workflow->getId());
		}
		$placesArray = $workflow->getPlacesArray();
		foreach ($placesArray as $place)
		{
			if($place->getPlacetype() == WorkflowHelper::PLACE_TYPE_END)
			{
				return $place;
			}
		}
		return null;
	}

	/**
	 * @param string $startTaskId
	 * @return boolean
	 */
	public function hasWorkflowStartTaskId($startTaskId)
	{
		$query = $this->createQuery();
		$query->add(Restrictions::eq('starttaskid', $startTaskId));
		$query->setMaxResults(1);
		return count($query->find()) != 0;
	}
	
	/**
	 * @param workflow_persistentdocument_workflow $workflow
	 * @return boolean
	 */
	public function hasOtherActiveWorkflowDefinitions($workflow)
	{
		$query = $this->createQuery()->add(Restrictions::ne('id', $workflow->getId()));
		$query->add(Restrictions::in('publicationstatus', array('ACTIVE', 'PUBLICATED')));
		$query->add(Restrictions::eq('starttaskid', $workflow->getStarttaskid()));
		$endDate = $workflow->getEndpublicationdate();
		if ($endDate)
		{
			$query->add(Restrictions::orExp(Restrictions::isEmpty('startpublicationdate'), Restrictions::lt('startpublicationdate', $endDate)));
		}
		$startDate = $workflow->getStartpublicationdate();
		if ($startDate)
		{
			$query->add(Restrictions::orExp(Restrictions::isEmpty('endpublicationdate'), Restrictions::gt('endpublicationdate', $startDate)));
		}
		$query->setProjection(Projections::rowCount('count'));
		
		return f_util_ArrayUtils::firstElement($query->findColumn('count')) > 0;
	}
}