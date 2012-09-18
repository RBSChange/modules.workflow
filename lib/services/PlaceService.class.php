<?php
/**
 * @package modules.workflow
 * @method workflow_PlaceService getInstance()
 */
class workflow_PlaceService extends f_persistentdocument_DocumentService
{

	/**
	 * @return workflow_persistentdocument_place
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_workflow/place');
	}

	/**
	 * Create a query based on 'modules_workflow/place' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_workflow/place');
	}

	/**
	 * Updates associated arcs' labels.
	 * @param workflow_persistentdocument_place $place
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal).
	 */
	public function postSave($place, $parentNodeId)
	{
		$as = workflow_ArcService::getInstance();
		$arcsArray = $as->getArcsByPlace($place);
		foreach ($arcsArray as $arc)
		{
			$as->regenerateLabel($arc);
			$arc->save();
		}
	}

	/**
	 * Validate this place for the workflow definition (test if it is correctly connected).
	 * @param workflow_persistentdocument_place $place
	 * @return boolean
	 */
	public function validatePath($place)
	{
		// Check if this place has output and/or input arcs according to the place type.
		$hasinput = (count($this->getInputArcsArray($place)) > 0);
		$hasoutput = (count($this->getOutputArcsArray($place)) > 0);
		switch ($place->getPlacetype())
		{
			case WorkflowHelper::PLACE_TYPE_START :
				if ($hasoutput)
				{
					return true;
				}
				break;

			case WorkflowHelper::PLACE_TYPE_END :
				if (!$hasoutput && $hasinput)
				{
					return true;
				}
				break;

			default :
				if ($hasoutput && $hasinput)
				{
					return true;
				}
				break;
		}
		
		$workflow = $place->getWorkflow();
		$workflowService = $workflow->getDocumentService();
		$workflowService->setActivePublicationStatusInfo($workflow, 'm.workflow.bo.general.error-placebadlyconnected', array('id' => $place->getId()));
		return false;
	}

	/**
	 * Get all arcs leading to this place.
	 * @param workflow_persistentdocument_place $place
	 * @param string $precondition allow to filter arcs by precondition.
	 * @return array<workflow_persistentdocument_arc>
	 */
	public function getInputArcsArray($place, $precondition = null)
	{
		return workflow_ArcService::getInstance()->getArcsByPlace($place, WorkflowHelper::DIRECTION_TRANSITION_TO_PLACE, $precondition);
	}

	/**
	 * Get all arcs leaving this place.
	 * @param workflow_persistentdocument_place $place
	 * @param string $precondition allow to filter arcs by precondition.
	 * @return array<workflow_persistentdocument_arc>
	 */
	public function getOutputArcsArray($place, $precondition = null)
	{
		return workflow_ArcService::getInstance()->getArcsByPlace($place, WorkflowHelper::DIRECTION_PLACE_TO_TRANSITION, $precondition);
	}
}