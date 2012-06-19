<?php
/**
 * @package modules.workflow
 * @method workflow_TokenService getInstance()
 */
class workflow_TokenService extends f_persistentdocument_DocumentService
{
	/**
	 * @return workflow_persistentdocument_token
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_workflow/token');
	}

	/**
	 * Create a query based on 'modules_workflow/token' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_workflow/token');
	}

	/**
	 * Get the associated workflow.
	 * @param workflow_persistentdocument_token $token
	 * @return
	 */
	public function getWorkflow($token)
	{
		return $token->getCase()->getWorkflow();
	}

	/**
	 * Check the status to know if this token is active.
	 * @param workflow_persistentdocument_token $token
	 * @return boolean
	 */
	public function isActive($token)
	{
		return ($token->getPublicationstatus() == 'ACTIVE' || $token->isPublished());
	}

	/**
	 * Cancel this token.
	 * @param workflow_persistentdocument_token $token
	 */
	public function cancelToken($token)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : token = ' . $token->getId());
		}
		$token->setPublicationstatus('TRASH');
	}

	/**
	 * Consume this token.
	 * @param workflow_persistentdocument_token $token
	 */
	public function consume($token)
	{
		if (!$token)
		{
			Framework::error(__METHOD__ . ' : No token given');
			return;
		}
		else if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : token = ' . $token->getId());
		}

		$token->setPublicationstatus('FILED');

		// Cancel associated workitems.
		$arcsArray = workflow_PlaceService::getInstance()->getOutputArcsArray($token->getPlace());
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : There are ' . count($arcsArray) . ' output arcs for the place ' . $token->getPlace()->getId());
		}
		if ($arcsArray === null || count($arcsArray) == 0)
		{
			workflow_CaseService::getInstance()->close($token->getCase());
		}
		else
		{
			$cs = workflow_CaseService::getInstance();
			$wis = workflow_WorkitemService::getInstance();

			foreach ($arcsArray as $arc)
			{
				$workitemsArray = $cs->getActiveWorkitems($token->getCase(), $arc->getTransition()->getId());
				foreach ($workitemsArray as $workitem)
				{
					$wis->cancelWorkitem($workitem);
					if (Framework::isDebugEnabled())
					{
						Framework::debug(__METHOD__ . ' : Workitem cancelled ' . $workitem->getId());
					}
				}
			}
		}
	}

	/**
	 * Initialize a token.
	 * @param workflow_persistentdocument_case $case
	 * @param workflow_persistentdocument_place $place
	 * @return workflow_persistentdocument_token $token
	 */
	public function init($case, $place)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : place = ' . $place->getId());
		}
		$token = workflow_WorkflowEngineService::getInstance()->getNewTokenInstance();
		$token->setPlace($place);
		$token->setPublicationstatus('ACTIVE');
		$token->setDocumentid($case->getDocumentId());
		$token->setLabel($place->getLabel());
		$case->addToken($token);
		return $token;
	}

	/**
	 * Token's treatment.
	 * @param workflow_persistentdocument_token $token
	 */
	public function checks($token)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : token = ' . $token->getId());
		}
		$place = $token->getPlace();
		$case = $token->getCase();
		if ($place->getPlaceType() == WorkflowHelper::PLACE_TYPE_END)
		{
			$this->consume($token);
		}
		else
		{
			$transitionsArray = array();
			$arcsArray = workflow_PlaceService::getInstance()->getOutputArcsArray($place);
			foreach ($arcsArray as $arc)
			{
				$transitionsArray[] = $arc->getTransition();
			}

			$cs = workflow_CaseService::getInstance();
			$ts = workflow_TransitionService::getInstance();

			foreach ($transitionsArray as $transition)
			{
				$tokencounter = 0;
				$alltokenneeded = false;
				$arcsArray = $ts->getInputArcsArray($transition);
				foreach ($arcsArray as $arc)
				{
					if ($arc->getArcType() == WorkflowHelper::ARC_TYPE_AND_JOIN)
					{
						$alltokenneeded = true;
					}

					$otherToken = $cs->getActiveToken($case, $arc->getPlace()->getId());
					if ($otherToken !== null)
					{
						$tokencounter++;
					}
				}

				if (($alltokenneeded && $tokencounter == count($arcsArray)) || (!$alltokenneeded && $tokencounter != 0))
				{
					$cs->addNewWorkItem($case, $transition);
				}
			}
		}
	}
}