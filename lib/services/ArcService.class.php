<?php
/**
 * @package modules.workflow
 */
class workflow_ArcService extends f_persistentdocument_DocumentService
{
	/**
	 * @var workflow_ArcService
	 */
	private static $instance;

	/**
	 * @return workflow_ArcService
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
	 * @return workflow_persistentdocument_arc
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_workflow/arc');
	}

	/**
	 * Create a query based on 'modules_workflow/arc' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_workflow/arc');
	}

	/**
	 * @param workflow_persistentdocument_arc $document
	 * @param integer $parentNodeId
	 * @return boolean
	 */
	public function preSave($document, $parentNodeId = null)
	{
		// Clean the precondition if we're not in an explicit or.
		if ($document->getArctype() != WorkflowHelper::ARC_TYPE_EXPLICIT_OR_SPLIT)
		{
			$document->setPrecondition('');
		}

		// Automatically fill the label.
		$this->regenerateLabel($document);
	}

	/**
	 * Generate the arc label.
	 * @param workflow_persistentdocument_arc $arc
	 */
	public function regenerateLabel($arc)
	{
		$label = '';
		$placeLabel = ($arc->getPlace()) ? $arc->getPlace()->getLabel() : 'null';
		$transitionLabel = ($arc->getTransition()) ? $arc->getTransition()->getLabel() : 'null';
		if($arc->getDirection() == WorkflowHelper::DIRECTION_PLACE_TO_TRANSITION)
		{
			$label = $placeLabel . ' -> ' . $transitionLabel;
		}
		else
		{
			$label = $transitionLabel . ' -> ' . $placeLabel;
		}

		if ($arc->getPrecondition())
		{
			$label .= ' (' . $arc->getPrecondition() . ')';
		}
		$arc->setLabel($label);
	}

	/**
	 * Validate this arc for the workflow definition (test if it is correctly connected and if the type, direction and precondition are compatible).
	 * @param workflow_persistentdocument_arc $arc
	 * @return boolean
	 */
	public function validatePath($arc)
	{
		if (is_null($arc->getPlace()) || is_null($arc->getTransition()))
		{
			$error = f_Locale::translate('&modules.workflow.bo.general.Error-ArcBadlyConnected;', array('id' => $arc->getId()));
			workflow_WorkflowService::getInstance()->invalidate($arc->getWorkflow(), $error);
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' : ' . $error);
			}
			return false;
		}

		if (!$this->checkType($arc))
		{
			$error = f_Locale::translate('&modules.workflow.bo.general.Error-BadArcType;', array('id' => $arc->getId()));
			workflow_WorkflowService::getInstance()->invalidate($arc->getWorkflow(), $error);
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' : ' . $error);
			}
			return false;
		}


		if (!$this->checkPrecondition($arc))
		{
			$error = f_Locale::translate('&modules.workflow.bo.general.Error-BadArcPrecondition;', array('id' => $arc->getId()));
			workflow_WorkflowService::getInstance()->invalidate($arc->getWorkflow(), $error);
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' : ' . $error);
			}
			return false;
		}

		return true;
	}

	/**
	 * Check if the arc type is valid for the selected direction.
	 * @param workflow_persistentdocument_arc $arc
	 * @return boolean
	 */
	public function checkType($arc)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : Start for arc id ' . $arc->getId());
		}
		$ret = false;
		if ($arc->getDirection() == WorkflowHelper::DIRECTION_TRANSITION_TO_PLACE)
		{
			switch ($arc->getArctype())
			{
				case WorkflowHelper::ARC_TYPE_SEQUENTIAL;
				case WorkflowHelper::ARC_TYPE_AND_SPLIT;
				case WorkflowHelper::ARC_TYPE_EXPLICIT_OR_SPLIT;
				case WorkflowHelper::ARC_TYPE_OR_JOIN;
					$ret = true;
					break;
			}
		}
		else
		{
			switch ($arc->getArctype())
			{
				case WorkflowHelper::ARC_TYPE_SEQUENTIAL;
				case WorkflowHelper::ARC_TYPE_AND_JOIN;
				case WorkflowHelper::ARC_TYPE_IMPLICIT_OR_SPLIT;
					$ret = true;
					break;
			}
		}
		return $ret;
	}

	/**
	 * Check if the arc precondition is valid.
	 * @param workflow_persistentdocument_arc $arc
	 * @return boolean
	 */
	public function checkPrecondition($arc)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : Start for arc id ' . $arc->getId());
		}
		// Precondition is needed only with explicit or split.
		if ($arc->getArctype() == WorkflowHelper::ARC_TYPE_EXPLICIT_OR_SPLIT)
		{
			$precondition = $arc->getPrecondition();
			if (is_null($precondition) || $precondition == '')
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Validate the arc crossing.
	 * @param workflow_persistentdocument_arc $arc
	 * @param workflow_persistentdocument_workitem $workitem
	 * @return boolean
	 */
	public function guard($arc, $workitem = null)
	{
		if ($arc->getArctype() != WorkflowHelper::ARC_TYPE_EXPLICIT_OR_SPLIT)
		{
			return true;
		}
		$case = $workitem->getCase();
		$testValue = workflow_CaseService::getInstance()->getParameter($case, '__LAST_STATUS');
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . " : __LAST_STATUS = " . $testValue . ', precondition = ' . $arc->getPrecondition());
		}
		return ($arc->getPrecondition() == $testValue);
	}

	/**
	 * Get all arcs matching a given transition.
	 * @param workflow_persistentdocument_transition $transition
	 * @param string $direction allow to filter arcs by direction.
	 * @param string $precondition allow to filter arcs by precondition.
	 * @return array<workflow_persistentdocument_arc>
	 */
	public function getArcsByTransition($transition, $direction = null, $precondition = null)
	{
		if (!empty($transition))
		{
			return $this->getArcsByTransitionId($transition->getId(), $direction, $precondition);
		}
		else
		{
			return array();
		}
	}

	/**
	 * Get all arcs matching a given transition id.
	 * @param integer $transitionId
	 * @param string $direction allow to filter arcs by direction.
	 * @param string $precondition allow to filter arcs by precondition.
	 * @return array<workflow_persistentdocument_arc>
	 */
	public function getArcsByTransitionId($transitionId, $direction = null, $precondition = null)
	{
		$query = $this->createQuery();
		if (!empty($direction))
		{
			$query->add(Restrictions::eq('direction', $direction));
		}
		if (!empty($precondition))
		{
			$query->add(Restrictions::eq('precondition', $precondition));
		}
		$query->createCriteria('transition')->add(Restrictions::eq('id', $transitionId));
		return $query->find();
	}

	/**
	 * Get all arcs matching a given place.
	 * @param workflow_persistentdocument_place $place
	 * @param string $direction allow to filter arcs by direction.
	 * @param string $precondition allow to filter arcs by precondition.
	 * @return array<workflow_persistentdocument_arc>
	 */
	public function getArcsByPlace($place, $direction = null, $precondition = null)
	{
		if (!empty($place))
		{
			return $this->getArcsByPlaceId($place->getId(), $direction, $precondition);
		}
		else
		{
			return array();
		}
	}

	/**
	 * Get all arcs matching a given place id.
	 * @param integer $placeId
	 * @param string $direction allow to filter arcs by direction.
	 * @param string $precondition allow to filter arcs by precondition.
	 * @return array<workflow_persistentdocument_arc>
	 */
	public function getArcsByPlaceId($placeId, $direction = null, $precondition = null)
	{
		$query = $this->createQuery();
		if (!empty($direction))
		{
			$query->add(Restrictions::eq('direction', $direction));
		}
		if (!empty($precondition))
		{
			$query->add(Restrictions::eq('precondition', $precondition));
		}
		$query->createCriteria('place')->add(Restrictions::eq('id', $placeId));
		return $query->find();
	}
}