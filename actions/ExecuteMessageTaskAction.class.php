<?php
/**
 * workflow_ExecuteMessageTaskAction
 * @package modules.workflow.actions
 */
class workflow_ExecuteMessageTaskAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		// Get the parameters.
		$workitem = $this->getDocumentInstanceFromRequest($request);
		$decision = $request->getParameter('decision');
		$commentary = $request->getParameter('commentary');
		
		// Get user.
		$user = users_UserService::getInstance()->getCurrentBackEndUser();
		$userId = ($user !== null) ? $user->getId() : 0;
		$permissionService = f_permission_PermissionService::getInstance();
		$roleName = $workitem->getTransition()->getRoleid();
		$roleName = $permissionService->resolveRole($roleName, $workitem->getDocumentid());
		if ($roleName && !in_array($user->getId(), $permissionService->getUsersByRoleAndDocumentId($roleName, $workitem->getDocumentid())))
		{
			throw new BaseException('the current user ' . $userId . ' hasn\'t the requested role: ' . $roleName);
		}
				
		$case = $workitem->getCase();
		$caseService = workflow_CaseService::getInstance();
		$caseService->setParameter($case, '__LAST_DECISION', $decision);
		$caseService->setParameter($case, '__LAST_COMMENTARY', $commentary);
		
		$wis = workflow_WorkitemService::getInstance();
		$wis->messageTrigger($workitem, 'performed by ' . $userId);
		$case->save();
	
		return $this->sendJSON(array());
	}
}