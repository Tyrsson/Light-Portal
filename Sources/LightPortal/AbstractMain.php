<?php declare(strict_types=1);

/**
 * AbstractMain.php
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2023 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @version 2.1
 */

namespace Bugo\LightPortal;

use Bugo\LightPortal\Entities\Block;

if (! defined('SMF'))
	die('No direct access...');

abstract class AbstractMain
{
	use Helper;

	abstract public function hooks();

	protected function isPortalCanBeLoaded(): bool
	{
		if (! defined('LP_NAME') || isset($this->context['uninstalling']) || $this->request()->is('printpage')) {
			$this->modSettings['minimize_files'] = 0;
			return false;
		}

		return true;
	}

	protected function defineVars(): void
	{
		$this->context['allow_light_portal_view']             = $this->allowedTo('light_portal_view');
		$this->context['allow_light_portal_manage_blocks']    = $this->allowedTo('light_portal_manage_blocks');
		$this->context['allow_light_portal_manage_pages_own'] = $this->allowedTo('light_portal_manage_pages_own');
		$this->context['allow_light_portal_manage_pages_any'] = $this->allowedTo('light_portal_manage_pages_any');
		$this->context['allow_light_portal_approve_pages']    = $this->allowedTo('light_portal_approve_pages');

		$this->calculateNumberOfEntities();

		$this->context['lp_all_title_classes']   = $this->getTitleClasses();
		$this->context['lp_all_content_classes'] = $this->getContentClasses();
		$this->context['lp_block_placements']    = $this->getBlockPlacements();
		$this->context['lp_page_options']        = $this->getPageOptions();
		$this->context['lp_plugin_types']        = $this->getPluginTypes();
		$this->context['lp_content_types']       = $this->getContentTypes();

		$this->context['lp_enabled_plugins']  = empty($this->modSettings['lp_enabled_plugins'])  ? [] : explode(',', $this->modSettings['lp_enabled_plugins']);
		$this->context['lp_frontpage_pages']  = empty($this->modSettings['lp_frontpage_pages'])  ? [] : explode(',', $this->modSettings['lp_frontpage_pages']);
		$this->context['lp_frontpage_topics'] = empty($this->modSettings['lp_frontpage_topics']) ? [] : explode(',', $this->modSettings['lp_frontpage_topics']);

		$this->context['lp_header_panel_width'] = empty($this->modSettings['lp_header_panel_width']) ? 12 : (int) $this->modSettings['lp_header_panel_width'];
		$this->context['lp_left_panel_width']   = empty($this->modSettings['lp_left_panel_width'])   ? ['md' => 3, 'lg' => 3, 'xl' => 2] : $this->jsonDecode($this->modSettings['lp_left_panel_width'], true, false);
		$this->context['lp_right_panel_width']  = empty($this->modSettings['lp_right_panel_width'])  ? ['md' => 3, 'lg' => 3, 'xl' => 2] : $this->jsonDecode($this->modSettings['lp_right_panel_width'], true, false);
		$this->context['lp_footer_panel_width'] = empty($this->modSettings['lp_footer_panel_width']) ? 12 : (int) $this->modSettings['lp_footer_panel_width'];

		$this->context['lp_panel_direction'] = $this->jsonDecode($this->modSettings['lp_panel_direction'] ?? '', true, false);

		$this->context['lp_active_blocks'] = (new Block)->getActive();

		$this->context['lp_icon_set'] = $this->getEntityList('icon');
	}

	protected function loadAssets(): void
	{
		if (! empty($this->modSettings['lp_fa_source'])) {
			if ($this->modSettings['lp_fa_source'] === 'css_local') {
				$this->loadCSSFile('all.min.css', [], 'portal_fontawesome');
			} elseif ($this->modSettings['lp_fa_source'] === 'custom' && $this->modSettings['lp_fa_custom']) {
				$this->loadExtCSS(
					$this->modSettings['lp_fa_custom'],
					['seed' => false],
					'portal_fontawesome'
				);
			}
		}

		$this->loadCSSFile('light_portal/flexboxgrid.css');
		$this->loadCSSFile('light_portal/portal.css');
		$this->loadCSSFile('custom_frontpage.css');
	}

	/**
	 * Remove unnecessary areas for the standalone mode
	 *
	 * Удаляем ненужные в автономном режиме области
	 */
	protected function unsetDisabledActions(array &$data): void
	{
		$disabled_actions = empty($this->modSettings['lp_disabled_actions']) ? [] : explode(',', $this->modSettings['lp_disabled_actions']);
		$disabled_actions[] = 'home';
		$disabled_actions = array_flip($disabled_actions);

		foreach (array_keys($data) as $action) {
			if (array_key_exists($action, $disabled_actions))
				unset($data[$action]);
		}

		if (array_key_exists('search', $disabled_actions))
			$this->context['allow_search'] = false;

		if (array_key_exists('moderate', $disabled_actions))
			$this->context['allow_moderation_center'] = false;

		if (array_key_exists('calendar', $disabled_actions))
			$this->context['allow_calendar'] = false;

		if (array_key_exists('mlist', $disabled_actions))
			$this->context['allow_memberlist'] = false;

		$this->context['lp_disabled_actions'] = $disabled_actions;
	}

	/**
	 * Fix canonical url for forum action
	 *
	 * Исправляем канонический адрес для области forum
	 */
	protected function fixCanonicalUrl(): void
	{
		if ($this->request()->is('forum'))
			$this->context['canonical_url'] = $this->scripturl . '?action=forum';
	}

	/**
	 * Change the link tree
	 *
	 * Меняем дерево ссылок
	 */
	protected function fixLinktree(): void
	{
		if (empty($this->context['current_board']) && $this->request()->hasNot('c') || empty($this->context['linktree'][1]))
			return;

		$old_url = explode('#', $this->context['linktree'][1]['url']);

		if (! empty($old_url[1]))
			$this->context['linktree'][1]['url'] = $this->scripturl . '?action=forum#' . $old_url[1];
	}

	/**
	 * Allow forum action page indexing
	 *
	 * Разрешаем индексацию главной страницы форума
	 */
	protected function fixForumIndexing(): void
	{
		$this->context['robot_no_index'] = false;
	}

	/**
	 * Show the script execution time and the number of the portal queries
	 *
	 * Отображаем время выполнения скрипта и количество запросов к базе
	 */
	protected function showDebugInfo(): void
	{
		if (empty($this->modSettings['lp_show_debug_info']) || empty($this->context['user']['is_admin']) || empty($this->context['template_layers']) || $this->request()->is('devtools'))
			return;

		$this->context['lp_load_page_stats'] = sprintf($this->txt['lp_load_page_stats'], round(microtime(true) - $this->context['lp_load_time'], 3), $this->context['lp_num_queries']);

		$this->loadTemplate('LightPortal/ViewDebug');

		if (empty($key = array_search('lp_portal', $this->context['template_layers']))) {
			$this->context['template_layers'][] = 'debug';
			return;
		}

		$this->context['template_layers'] = array_merge(
			array_slice($this->context['template_layers'], 0, $key, true),
			['debug'],
			array_slice($this->context['template_layers'], $key, null, true)
		);
	}

	protected function promoteTopic(): void
	{
		if (empty($this->user_info['is_admin']) || $this->request()->hasNot('t'))
			return;

		$topic = $this->request('t');

		if (($key = array_search($topic, $this->context['lp_frontpage_topics'])) !== false) {
			unset($this->context['lp_frontpage_topics'][$key]);
		} else {
			$this->context['lp_frontpage_topics'][] = $topic;
		}

		$this->updateSettings(['lp_frontpage_topics' => implode(',', $this->context['lp_frontpage_topics'])]);

		$this->redirect('topic=' . $topic);
	}

	private function calculateNumberOfEntities(): void
	{
		if (($num_entities = $this->cache()->get('num_active_entities_u' . $this->user_info['id'])) === null) {
			$request = $this->smcFunc['db_query']('', '
				SELECT
					(
						SELECT COUNT(b.block_id)
						FROM {db_prefix}lp_blocks b
						WHERE b.status = {int:active}' . ($this->user_info['is_admin'] ? '
							AND b.user_id = 0' : '
							AND b.user_id = {int:user_id}') . '
					) AS num_blocks,
					(
						SELECT COUNT(p.page_id)
						FROM {db_prefix}lp_pages p
						WHERE p.status = {int:active}' . ($this->user_info['is_admin'] ? '' : '
							AND p.author_id = {int:user_id}') . '
					) AS num_pages,
					(
						SELECT COUNT(page_id)
						FROM {db_prefix}lp_pages
						WHERE author_id = {int:user_id}
					) AS num_my_pages,
					(
						SELECT COUNT(page_id)
						FROM {db_prefix}lp_pages
						WHERE status = {int:unapproved}
					) AS num_unapproved_pages',
				[
					'active'     => 1,
					'unapproved' => 2,
					'user_id'    => $this->user_info['id']
				]
			);

			$num_entities = $this->smcFunc['db_fetch_assoc']($request);
			array_walk($num_entities, fn(&$item) => $item = (int) $item);

			$this->smcFunc['db_free_result']($request);
			$this->context['lp_num_queries']++;

			$this->cache()->put('num_active_entities_u' . $this->user_info['id'], $num_entities);
		}

		$this->context['lp_quantities'] = [
			'active_blocks'    => $num_entities['num_blocks'],
			'active_pages'     => $num_entities['num_pages'],
			'my_pages'         => $num_entities['num_my_pages'],
			'unapproved_pages' => $num_entities['num_unapproved_pages'],
		];
	}

	private function getBlockPlacements(): array
	{
		return array_combine(['header', 'top', 'left', 'right', 'bottom', 'footer'], $this->txt['lp_block_placement_set']);
	}

	private function getPageOptions(): array
	{
		return array_combine(['show_title', 'show_author_and_date', 'show_related_pages', 'allow_comments'], $this->txt['lp_page_options']);
	}

	private function getPluginTypes(): array
	{
		return array_combine(
			['block', 'ssi', 'editor', 'comment', 'parser', 'article', 'frontpage', 'impex', 'block_options', 'page_options', 'icons', 'seo', 'other'],
			$this->txt['lp_plugins_types']
		);
	}
}
