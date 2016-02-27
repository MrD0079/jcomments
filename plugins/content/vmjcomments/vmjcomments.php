<?php
/**
 * JComments - Joomla Virtuemart 2+ Comment System
 *
 * @version 3.0
 * @package JComments
 * @author Patrick Kohl
 * @copyright (C) 2016 by Studio42 France (http://www.st42.fr)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

include_once(JPATH_ROOT . '/components/com_jcomments/jcomments.legacy.php');

if (!defined('JCOMMENTS_JVERSION')) {
	return;
}

jimport('joomla.plugin.plugin');

/**
 * Plugin for attaching comments list and form to content item
 */
class plgContentVmJcomments extends JPlugin
{
	function plgContentVmJcomments(&$subject, $config)
	{
		parent::__construct($subject, $config);
	}

	function onPrepareContent(&$article, &$params, $limitstart = 0)
	{
		require_once(JPATH_ROOT . '/components/com_jcomments/helpers/content.php');


		// check whether plugin has been unpublished
		if (!JPluginHelper::isEnabled('content', 'vmjcomments')) {
			JCommentsContentPluginHelper::clear($article, true);

			return '';
		}

		$app = JFactory::getApplication('site');
		$option = $app->input->get('option');
		$view = $app->input->get('view');
		if (!isset($article->virtuemart_product_id)) {
			return '';
		}

		if (!isset($params) || $params == null) {
			$params = new JRegistry('');
		} else if (isset($params->_raw) && strpos($params->_raw, 'moduleclass_sfx') !== false) {
			return '';
		}

		require_once(JPATH_ROOT . '/components/com_jcomments/jcomments.class.php');

		JCommentsContentPluginHelper::processForeignTags($article);

		$config = JCommentsFactory::getConfig();

		$categoryEnabled = 1;//JCommentsContentPluginHelper::checkCategory($article->catid);
		$commentsEnabled = 1;//JCommentsContentPluginHelper::isEnabled($article) || $categoryEnabled;
		$commentsDisabled = 0;//JCommentsContentPluginHelper::isDisabled($article) || !$commentsEnabled;
		$commentsLocked = 0;//JCommentsContentPluginHelper::isLocked($article);

		$archivesState = 2;

		// if (isset($article->state) && $article->state == $archivesState && $this->params->get('enable_for_archived', 0) == 0) {
			// $commentsLocked = true;
		// }

		$config->set('comments_on', intval($commentsEnabled));
		$config->set('comments_off', intval($commentsDisabled));
		$config->set('comments_locked', intval($commentsLocked));

		if ($this->params->get('show_comments_event') == 'onPrepareContent') {
			$isEnabled = ($config->getInt('comments_on', 0) == 1) && ($config->getInt('comments_off', 0) == 0);
			if ($isEnabled && $view == 'productdetails') {
				require_once(JPATH_ROOT . '/components/com_jcomments/jcomments.php');

				$comments = JComments::show($article->virtuemart_product_id, 'com_virtuemart', $article->product_name);

				if (strpos($article->text, '{jvmcomments}') !== false) {
					$article->text = str_replace('{jvmcomments}', $comments, $article->text);
				} else {
					$article->text .= $comments;
				}
			}
		}
		JCommentsContentPluginHelper::clear($article, true);

		return '';
	}

	function onAfterDisplayContent(&$article, &$params, $limitstart = 0)
	{
		
// var_dump($article);jexit();
		// if ($this->params->get('show_comments_event', 'onAfterDisplayContent') == 'onAfterDisplayContent') {
			require_once(JPATH_ROOT . '/components/com_jcomments/helpers/content.php');

			$app = JFactory::getApplication('site');
			$view = $app->input->get('view');

			// check whether plugin has been unpublished and display the right view
			if (!JPluginHelper::isEnabled('content', 'vmjcomments')
				|| $view != 'productdetails'
				|| $params->get('popup')
				|| $app->input->get('print')
			) {
				JCommentsContentPluginHelper::clear($article, true);

				return '';
			}

			require_once(JPATH_ROOT . '/components/com_jcomments/jcomments.php');

			$config = JCommentsFactory::getConfig();
			// $isEnabled = ($config->getInt('comments_on', 0) == 1) && ($config->getInt('comments_off', 0) == 0);
			$isEnabled = true; // temp STUDIO42
			if ($isEnabled) {
				JCommentsContentPluginHelper::clear($article, true);

				return JComments::show($article->virtuemart_product_id, 'com_virtuemart', $article->product_name);
			}
		// }

		return '';
	}

	function _onContentBeforeDisplay($context, &$article, &$params, $page = 0)
	{
		if ($context == 'com_virtuemart.productdetails') {
			$app = JFactory::getApplication('site');
			$view = $app->input->get('view');


			// do not display comments in modules
			$data = $params->toArray();
			if (isset($data['moduleclass_sfx'])) {
				return;
			}

			$originalText = isset($article->text) ? $article->text : '';
			$article->text = '';
			$this->onPrepareContent($article, $params, $page);

			if (isset($article->text)) {
				if (strpos($originalText, '{jcomments}') !== false) {
					$originalText = str_replace('{jcomments}', $article->text, $originalText);
				}
			}

			$article->text = $originalText;
			JCommentsContentPluginHelper::clear($article);
		}
	}

	function onContentAfterDisplay($context, &$article, &$params, $limitstart = 0)
	{
		if ($context == 'com_virtuemart.productdetails') {
			// do not display comments in modules
			$data = $params->toArray();
			if (isset($data['moduleclass_sfx'])) {
				return '';
			}

			return $this->onAfterDisplayContent($article, $params, $limitstart);
		}

		return '';
	}
	// note : Never called
	function onContentAfterDelete($context, $data)
	{
		if ($context == 'com_virtuemart.productdetails') {
			require_once(JPATH_ROOT . '/components/com_jcomments/models/jcomments.php');

			JCommentsModel::deleteComments((int)$data->virtuemart_product_id, 'com_virtuemart');

			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->delete();
			$query->from($db->quoteName('#__jcomments_subscriptions'));
			$query->where($db->quoteName('object_id') . ' = ' . (int)$data->virtuemart_product_id);
			$query->where($db->quoteName('object_group') . ' = ' . $db->Quote('com_virtuemart'));
			$db->setQuery($query);
			$db->execute();
		}
	}
	// note : Never called
	function onContentAfterSave($context, $article, $isNew)
	{
		// Check we are handling the frontend edit form.
		if ($context == 'com_virtuemart.form' && !$isNew) {
			require_once(JPATH_ROOT . '/components/com_jcomments/helpers/content.php');
			require_once(JPATH_ROOT . '/components/com_jcomments/helpers/object.php');
			JCommentsObjectHelper::storeObjectInfo($article->virtuemart_product_id, 'com_virtuemart');
		}
	}
}