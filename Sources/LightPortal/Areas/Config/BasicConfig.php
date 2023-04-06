<?php declare(strict_types=1);

/**
 * BasicConfig.php
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2023 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @version 2.1
 */

namespace Bugo\LightPortal\Areas\Config;

use Bugo\LightPortal\Helper;
use Bugo\LightPortal\Entities\FrontPage;
use Bugo\LightPortal\Repositories\PageRepository;

if (! defined('SMF'))
	die('No direct access...');

final class BasicConfig
{
	use Helper;

	private FrontPage $frontPage;

	private PageRepository $pageRepository;

	public function __construct()
	{
		$this->frontPage = new FrontPage;

		$this->pageRepository = new PageRepository();
	}

	/**
	 * Output general settings
	 *
	 * Выводим общие настройки
	 */
	public function show(): void
	{
		$this->prepareAliasList();
		$this->prepareTopicList();

		$this->context['page_title'] = $this->context['settings_title'] = $this->txt['lp_base'];
		$this->context['post_url']   = $this->scripturl . '?action=admin;area=lp_settings;sa=basic;save';

		$this->context['permissions_excluded']['light_portal_manage_blocks']    = [-1, 0];
		$this->context['permissions_excluded']['light_portal_manage_pages_own'] = [-1, 0];
		$this->context['permissions_excluded']['light_portal_manage_pages_any'] = [-1, 0];
		$this->context['permissions_excluded']['light_portal_approve_pages']    = [-1, 0];

		// Initial settings
		$addSettings = [];
		if (! isset($this->modSettings['lp_frontpage_title']))
			$addSettings['lp_frontpage_title'] = str_replace(array("'", "\""), "", $this->context['forum_name']);
		if (! isset($this->modSettings['lp_frontpage_alias']))
			$addSettings['lp_frontpage_alias'] = 'home';
		if (! isset($this->modSettings['lp_show_views_and_comments']))
			$addSettings['lp_show_views_and_comments'] = 1;
		if (! isset($this->modSettings['lp_frontpage_article_sorting']))
			$addSettings['lp_frontpage_article_sorting'] = 1;
		if (! isset($this->modSettings['lp_num_items_per_page']))
			$addSettings['lp_num_items_per_page'] = 10;
		if (! isset($this->modSettings['lp_standalone_url']))
			$addSettings['lp_standalone_url'] = $this->boardurl . '/portal.php';
		if (! isset($this->modSettings['lp_prohibit_php']))
			$addSettings['lp_prohibit_php'] = 1;
		$this->updateSettings($addSettings);

		$this->context['lp_frontpage_modes'] = array_combine(
			[0, 'chosen_page', 'all_pages', 'chosen_pages', 'all_topics', 'chosen_topics', 'chosen_boards'],
			$this->txt['lp_frontpage_mode_set']
		);

		$this->context['board_list'] = $this->getBoardList();

		$this->context['lp_column_set'] = array_map(fn($item) => $this->translate('lp_frontpage_num_columns_set', ['columns' => $item]), [1, 2, 3, 4, 6]);

		$this->context['lp_frontpage_layouts'] = $this->frontPage->getLayouts();

		$this->context['lp_all_categories'] = $this->getAllCategories();

		$this->context['lp_all_pages'] = $this->pageRepository->getAll(0, $this->pageRepository->getTotalCount(), 'p.page_id DESC', 'AND p.status = 1');

		$this->context['lp_selected_topics'] = $this->getSelectedTopics();

		$config_vars = [
			['callback', 'frontpage_mode_settings'],
			['title', 'lp_standalone_mode_title'],
			['callback', 'standalone_mode_settings'],
			['title', 'edit_permissions'],
			['check', 'lp_prohibit_php', 'invalid' => true],
			['permissions', 'light_portal_view', 'help' => 'permissionhelp_light_portal_view'],
			['permissions', 'light_portal_manage_blocks', 'help' => 'permissionhelp_light_portal_manage_blocks'],
			['permissions', 'light_portal_manage_pages_own', 'help' => 'permissionhelp_light_portal_manage_pages_own'],
			['permissions', 'light_portal_manage_pages_any', 'help' => 'permissionhelp_light_portal_manage_pages'],
			['permissions', 'light_portal_approve_pages', 'help' => 'permissionhelp_light_portal_approve_pages'],
		];

		$this->loadTemplate('LightPortal/ManageSettings');

		// Save
		if ($this->request()->has('save')) {
			$this->checkSession();

			if ($this->request()->isNotEmpty('lp_image_placeholder'))
				$this->post()->put('lp_image_placeholder', $this->validate($this->request('lp_image_placeholder'), 'url'));

			if ($this->request()->isNotEmpty('lp_standalone_url'))
				$this->post()->put('lp_standalone_url', $this->validate($this->request('lp_standalone_url'), 'url'));

			$save_vars = $config_vars;
			$save_vars[] = ['text', 'lp_frontpage_mode'];

			if ($this->request()->has('lp_frontpage_alias'))
				$save_vars[] = ['text', 'lp_frontpage_alias'];

			if ($this->request()->isNotEmpty('lp_frontpage_mode') && $this->request()->hasNot('lp_frontpage_alias')) {
				$save_vars[] = ['text', 'lp_frontpage_title'];
				$save_vars[] = ['text', 'lp_frontpage_categories'];
				$save_vars[] = ['text', 'lp_frontpage_boards'];
				$save_vars[] = ['text', 'lp_frontpage_pages'];
				$save_vars[] = ['text', 'lp_frontpage_topics'];
				$save_vars[] = ['check', 'lp_show_images_in_articles'];
				$save_vars[] = ['text', 'lp_image_placeholder'];
				$save_vars[] = ['check', 'lp_show_teaser'];
				$save_vars[] = ['check', 'lp_show_author'];
				$save_vars[] = ['check', 'lp_show_views_and_comments'];
				$save_vars[] = ['check', 'lp_frontpage_order_by_replies'];
				$save_vars[] = ['int', 'lp_frontpage_article_sorting'];
				$save_vars[] = ['text', 'lp_frontpage_layout'];
				$save_vars[] = ['int', 'lp_frontpage_num_columns'];
				$save_vars[] = ['int', 'lp_show_pagination'];
				$save_vars[] = ['check', 'lp_use_simple_pagination'];
				$save_vars[] = ['int', 'lp_num_items_per_page'];
			}

			$save_vars[] = ['check', 'lp_standalone_mode'];
			$save_vars[] = ['text', 'lp_standalone_url'];
			$save_vars[] = ['text', 'lp_disabled_actions'];

			$this->saveDBSettings($save_vars);

			$this->session()->put('adm-save', true);
			$this->cache()->flush();

			$this->redirect('action=admin;area=lp_settings;sa=basic');
		}

		$this->prepareDBSettingContext($config_vars);
	}

	private function prepareAliasList(): void
	{
		if ($this->request()->hasNot('alias_list'))
			return;

		$data = $this->request()->json();

		if (empty($search = $data['search']))
			return;

		$results = $this->pageRepository->getAll(0, 30, 'alias', 'AND INSTR(LOWER(p.alias), {string:string}) > 0', ['string' => $this->smcFunc['strtolower']($search)]);
		$results = array_column($results, 'alias');
		array_walk($results, function (&$item) {
			$item = ['value' => $item];
		});

		exit(json_encode($results));
	}

	private function getSelectedTopics(): array
	{
		if (empty($this->modSettings['lp_frontpage_topics']))
			return [];

		$request = $this->smcFunc['db_query']('', '
			SELECT t.id_topic, m.subject
			FROM {db_prefix}topics AS t
				INNER JOIN {db_prefix}messages AS m ON (m.id_msg = t.id_first_msg)
			WHERE t.id_topic IN ({array_int:topics})',
			[
				'topics' => explode(',', $this->modSettings['lp_frontpage_topics']),
			]
		);

		$topics = [];
		while ($row = $this->smcFunc['db_fetch_assoc']($request)) {
			$this->censorText($row['subject']);

			$topics[$row['id_topic']] = str_replace(array("'", "\""), "", $row['subject']);
		}

		$this->smcFunc['db_free_result']($request);
		$this->context['lp_num_queries']++;

		return $topics;
	}
}
