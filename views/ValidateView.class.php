<?php
/**
 * @package modules.workflow
 */
class workflow_ValidateView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$this->forceModuleName('workflow');
		$this->setTemplateName('Workflow-Validate', K::XUL);
		$task = $request->getAttribute('task');
		$lang = $task->getWorkitem()->getCase()->getlang();
		try
		{
			RequestContext::getInstance()->beginI18nWork($lang);

			$workDocument = DocumentHelper::getDocumentInstance($task->getWorkitem()->getDocumentid());
			$model = $workDocument->getPersistentModel();
			$document = DocumentHelper::getPropertiesOf($workDocument);
			$document['id'] = $workDocument->getId();
			$document['taskId'] = $task->getId();
			$document["type"] = f_Locale::translate($model->getLabel()) . " (" . $model->getDocumentName() . ")";
			$document['previewUrl'] = LinkHelper::getDocumentUrl($workDocument, null,
				array(website_DisplayAction::DISABLE_PUBLICATION_WORKFLOW => 'true', K::LANG_ACCESSOR => $lang));
			RequestContext::getInstance()->endI18nWork();
		}
		catch (Exception $e)
		{
			RequestContext::getInstance()->endI18nWork($e);
		}
		$this->setAttribute('document', $document);

		$this->setAttribute(
           'cssInclusion',
           website_StyleService::getInstance()
	    	  ->registerStyle('modules.dashboard.dashboard')
	    	  ->registerStyle('modules.uixul.bindings')
	    	  ->registerStyle('modules.uixul.backoffice')
	    	  ->execute(K::XUL)
	    );

		// include JavaScript
		$jss = website_JsService::getInstance();
		$jss->registerScript('modules.uixul.lib.default')->registerScript('modules.dashboard.lib.js.dashboardwidget');
        $this->setAttribute('scriptInclusion', $jss->executeInline(K::XUL));
	}
}