<?php
/**
 * @package modules.workflow
 */
class workflow_WorkflowService extends f_persistentdocument_DocumentService
{
	/**
	 * @var workflow_WorkflowService
	 */
	private static $instance;

	/**
	 * @return workflow_WorkflowService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return workflow_persistentdocument_workflow
	 */
	public function getNewDocumentInstance()
	{
		$workflow = $this->getNewDocumentInstanceByModelName('modules_workflow/workflow');
		$wds = workflow_WorkflowDesignerService::getInstance();

		// Automatically add the start place.
		$startPlace = $wds->getNewPlaceInstance();
		$startPlace->setPlacetype(WorkflowHelper::PLACE_TYPE_START);
		$startPlace->setLabel(f_Locale::translate('&modules.workflow.bo.general.AutoGenerated-StartPlace;'));
		$workflow->addPlaces($startPlace);

		// Automatically add the end place.
		$endPlace = $wds->getNewPlaceInstance();
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
		return $this->pp->createQuery('modules_workflow/workflow');
	}

	/**
	 * @param workflow_persistentdocument_workflow $document
	 * @return void
	 */
	protected function preDelete($document)
	{
		// Delete all cases associated to the deleted workflow.
		$query = $this->pp->createQuery('modules_workflow/case');
		$query->createCriteria('workflow')->add(Restrictions::eq('id', $document->getId()));
		$casesArray = $this->pp->find($query);
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
			$this->setActivePublicationStatusInfo($document, '&modules.workflow.bo.general.Error-TaskAndLabelNeeded;');
			return false;
		}

		// Check if there is a start place and a end place.
		if ($this->getStartPlace($document) === null || $this->getEndPlace($document) === null)
		{
			$this->setActivePublicationStatusInfo($document, '&modules.workflow.bo.general.Error-StartAndEndPlacesNeeded;');
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
	 * @param String $startTaskId
	 * @return Boolean
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
	
	// Deprecated.
	
	/**
	 * @deprecated (will be removed in 4.0) use publishIfPossible instead
	 */
	public function validatePath($workflow)
	{
		return $this->publishIfPossible($workflow->getId());
	}

	/**
	 * @deprecated (will be removed in 4.0) use publishIfPossible instead
	 */
	public function invalidate($workflow, $errors = null, $doSave = true)
	{
		$this->publishIfPossible($workflow->getId());
	}
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	public function isDefinitionValid($workflow)
	{
		return ($workflow->getPublicationstatus() == 'ACTIVE' || $workflow->isPublished());
	}
}