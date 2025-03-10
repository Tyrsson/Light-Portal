<?php declare(strict_types=1);

/**
 * Page.php
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2024 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @version 2.6
 */

namespace Bugo\LightPortal\Actions;

use Bugo\Compat\{Config, ErrorHandler, Lang};
use Bugo\Compat\{PageIndex, Theme, User, Utils};
use Bugo\LightPortal\Helper;
use Bugo\LightPortal\Repositories\PageRepository;
use Bugo\LightPortal\Utils\{Content, Icon};
use IntlException;

if (! defined('SMF'))
	die('No direct access...');

final class Page implements PageInterface
{
	use Helper;

	private PageRepository $repository;

	public function __construct()
	{
		$this->repository = new PageRepository();
	}

	/**
	 * @throws IntlException
	 */
	public function show(): void
	{
		User::mustHavePermission('light_portal_view');

		$alias = $this->request(LP_PAGE_PARAM);

		if (empty($alias)) {
			if ($this->isFrontpageMode('chosen_page') && Config::$modSettings['lp_frontpage_alias']) {
				Utils::$context['lp_page'] = $this->getDataByAlias(Config::$modSettings['lp_frontpage_alias']);
			} else {
				Config::updateModSettings(['lp_frontpage_mode' => 0]);
			}
		} else {
			$alias = explode(';', $alias)[0];

			if ($this->isFrontpage($alias))
				Utils::redirectexit('action=' . LP_ACTION);

			Utils::$context['lp_page'] = $this->getDataByAlias($alias);
		}

		if (empty(Utils::$context['lp_page'])) {
			$this->changeErrorPage();
			ErrorHandler::fatalLang('lp_page_not_found', status: 404);
		}

		if (empty(Utils::$context['lp_page']['can_view'])) {
			$this->changeErrorPage();
			ErrorHandler::fatalLang('cannot_light_portal_view_page');
		}

		if (empty(Utils::$context['lp_page']['status']) && empty(Utils::$context['lp_page']['can_edit'])) {
			$this->changeErrorPage();
			ErrorHandler::fatalLang('lp_page_not_activated');
		}

		if (Utils::$context['lp_page']['created_at'] > time())
			Utils::sendHttpStatus(404);

		Utils::$context['lp_page']['errors'] = [];
		if (empty(Utils::$context['lp_page']['status']) && Utils::$context['lp_page']['can_edit'])
			Utils::$context['lp_page']['errors'][] = Lang::$txt['lp_page_visible_but_disabled'];

		Utils::$context['lp_page']['content'] = Content::parse(
			Utils::$context['lp_page']['content'], Utils::$context['lp_page']['type']
		);

		if (empty($alias)) {
			Utils::$context['page_title'] = $this->getTranslatedTitle(
				Utils::$context['lp_page']['titles']
			) ?: Lang::$txt['lp_portal'];

			Utils::$context['canonical_url'] = Config::$scripturl;
			Utils::$context['linktree'][] = [
				'name' => Lang::$txt['lp_portal'],
			];
		} else {
			Utils::$context['page_title'] = $this->getTranslatedTitle(
				Utils::$context['lp_page']['titles']
			) ?: Lang::$txt['lp_post_error_no_title'];

			Utils::$context['canonical_url'] = LP_PAGE_URL . $alias;

			if (isset(Utils::$context['lp_page']['category'])) {
				Utils::$context['linktree'][] = [
					'name' => Utils::$context['lp_page']['category'],
					'url'  => LP_BASE_URL . ';sa=categories;id=' . Utils::$context['lp_page']['category_id'],
				];
			}

			Utils::$context['linktree'][] = [
				'name' => Utils::$context['page_title'],
			];
		}

		Utils::$context['lp_page']['url'] = Utils::$context['canonical_url'] . (
			$this->request()->has(LP_PAGE_PARAM) ? ';' : '?'
		);

		Theme::loadTemplate('LightPortal/ViewPage');

		Utils::$context['sub_template'] = 'show_page';

		$this->promote();
		$this->setMeta();
		$this->preparePrevNextLinks();
		$this->prepareRelatedPages();
		$this->prepareComments();
		$this->updateNumViews();

		Theme::loadJavaScriptFile('light_portal/bundle.min.js', ['defer' => true]);
	}

	public function getDataByAlias(string $alias): array
	{
		if (empty($alias))
			return [];

		$data = $this->cache('page_' . $alias)
			->setFallback(PageRepository::class, 'getData', $alias);

		$this->repository->prepareData($data);

		return $data;
	}

	public function showAsCards(PageListInterface $entity): void
	{
		if (empty(Config::$modSettings['lp_show_items_as_articles']))
			return;

		$start = (int) $this->request('start');
		$limit = (int) Config::$modSettings['lp_num_items_per_page'] ?? 12;

		$itemsCount = $entity->getTotalCount();

		$front = new FrontPage();
		$front->updateStart($itemsCount, $start, $limit);

		$sort     = $front->getOrderBy();
		$articles = $entity->getPages($start, $limit, $sort);

		Utils::$context['page_index'] = new PageIndex(
			Utils::$context['canonical_url'], $start, $itemsCount, $limit
		);

		Utils::$context['start'] = $this->request()->get('start');

		Utils::$context['lp_frontpage_articles']    = $articles;
		Utils::$context['lp_frontpage_num_columns'] = $front->getNumColumns();

		Utils::$context['template_layers'][] = 'sorting';

		$front->prepareTemplates();

		Utils::obExit();
	}

	public function getList(): array
	{
		return [
			'items_per_page' => Config::$modSettings['defaultMaxListItems'] ?: 50,
			'title' => Utils::$context['page_title'],
			'no_items_label' => Lang::$txt['lp_no_items'],
			'base_href' => Utils::$context['canonical_url'],
			'default_sort_col' => 'date',
			'columns' => [
				'date' => [
					'header' => [
						'value' => Lang::$txt['date']
					],
					'data' => [
						'db'    => 'date',
						'class' => 'centertext'
					],
					'sort' => [
						'default' => 'p.created_at DESC, p.updated_at DESC',
						'reverse' => 'p.created_at, p.updated_at'
					]
				],
				'title' => [
					'header' => [
						'value' => Lang::$txt['lp_title']
					],
					'data' => [
						'function' => static fn($entry) => '<a class="bbc_link' . (
							$entry['is_front']
								? ' new_posts" href="' . Config::$scripturl
								: '" href="' . LP_PAGE_URL . $entry['alias']
						) . '">' . $entry['title'] . '</a>',
						'class' => 'word_break'
					],
					'sort' => [
						'default' => 't.title DESC',
						'reverse' => 't.title'
					]
				],
				'author' => [
					'header' => [
						'value' => Lang::$txt['author']
					],
					'data' => [
						'function' => static fn($entry) => empty($entry['author']['name'])
							? Lang::$txt['guest_title']
							: '<a href="' . $entry['author']['link'] . '">' . $entry['author']['name'] . '</a>',
						'class' => 'centertext'
					],
					'sort' => [
						'default' => 'author_name DESC',
						'reverse' => 'author_name'
					]
				],
				'num_views' => [
					'header' => [
						'value' => Lang::$txt['views']
					],
					'data' => [
						'function' => static fn($entry) => $entry['views']['num'],
						'class' => 'centertext'
					],
					'sort' => [
						'default' => 'p.num_views DESC',
						'reverse' => 'p.num_views'
					]
				]
			],
			'form' => [
				'href' => Utils::$context['canonical_url']
			]
		];
	}

	private function changeErrorPage(): void
	{
		Utils::$context['error_link'] = Config::$scripturl;
		Lang::$txt['back'] = empty(Config::$modSettings['lp_frontpage_mode'])
			? Lang::$txt['lp_forum']
			: Lang::$txt['lp_portal'];

		if (Lang::$txt['back'] === Lang::$txt['lp_portal']) {
			Lang::$txt['back'] = Lang::$txt['lp_forum'];
			Utils::$context['error_link'] .= '">'
				. Lang::$txt['lp_portal']
				. '</a> <a class="button floatnone" href="' . Config::$scripturl . '?action=forum';
		}
	}

	private function promote(): void
	{
		if (empty(User::$info['is_admin']) || $this->request()->hasNot('promote'))
			return;

		$page = Utils::$context['lp_page']['id'];

		if (($key = array_search($page, Utils::$context['lp_frontpage_pages'])) !== false) {
			unset(Utils::$context['lp_frontpage_pages'][$key]);
		} else {
			Utils::$context['lp_frontpage_pages'][] = $page;
		}

		Config::updateModSettings([
			'lp_frontpage_pages' => implode(',', Utils::$context['lp_frontpage_pages'])
		]);

		Utils::redirectexit(Utils::$context['canonical_url']);
	}

	private function setMeta(): void
	{
		if (empty(Utils::$context['lp_page']))
			return;

		Utils::$context['meta_description'] = Utils::$context['lp_page']['description'];

		$keywords = [];
		if (isset(Utils::$context['lp_page']['tags'])) {
			$keywords = array_column(Utils::$context['lp_page']['tags'], 'title');

			Config::$modSettings['meta_keywords'] = implode(', ', $keywords);
		}

		Utils::$context['meta_tags'][] = [
			'prefix'   => 'article: https://ogp.me/ns/article#',
			'property' => 'og:type',
			'content'  => 'article',
		];

		Utils::$context['meta_tags'][] = [
			'prefix'   => 'article: https://ogp.me/ns/article#',
			'property' => 'article:author',
			'content'  => Utils::$context['lp_page']['author'],
		];

		Utils::$context['meta_tags'][] = [
			'prefix'   => 'article: https://ogp.me/ns/article#',
			'property' => 'article:published_time',
			'content'  => date('Y-m-d\TH:i:s', (int) Utils::$context['lp_page']['created_at']),
		];

		if (Utils::$context['lp_page']['updated_at']) {
			Utils::$context['meta_tags'][] = [
				'prefix'   => 'article: https://ogp.me/ns/article#',
				'property' => 'article:modified_time',
				'content'  => date('Y-m-d\TH:i:s', (int) Utils::$context['lp_page']['updated_at']),
			];
		}

		if (isset(Utils::$context['lp_page']['category'])) {
			Utils::$context['meta_tags'][] = [
				'prefix'   => 'article: https://ogp.me/ns/article#',
				'property' => 'article:section',
				'content'  => Utils::$context['lp_page']['category'],
			];
		}

		foreach ($keywords as $value) {
			Utils::$context['meta_tags'][] = [
				'prefix'   => 'article: https://ogp.me/ns/article#',
				'property' => 'article:tag',
				'content'  => $value,
			];
		}

		if (! (empty(Config::$modSettings['lp_page_og_image']) || empty(Utils::$context['lp_page']['image'])))
			Theme::$current->settings['og_image'] = Utils::$context['lp_page']['image'];
	}

	private function preparePrevNextLinks(): void
	{
		if (empty($page = Utils::$context['lp_page']) || empty(Config::$modSettings['lp_show_prev_next_links']))
			return;

		$titles = $this->getEntityData('title');

		[$prevId, $prevAlias, $nextId, $nextAlias] = $this->repository->getPrevNextLinks($page);

		if (! empty($prevAlias)) {
			Utils::$context['lp_page']['prev'] = [
				'link'  => LP_PAGE_URL . $prevAlias,
				'title' => $this->getTranslatedTitle($titles[$prevId])
			];
		}

		if (! empty($nextAlias)) {
			Utils::$context['lp_page']['next'] = [
				'link'  => LP_PAGE_URL . $nextAlias,
				'title' => $this->getTranslatedTitle($titles[$nextId])
			];
		}
	}

	private function prepareRelatedPages(): void
	{
		if (empty($page = Utils::$context['lp_page']) || empty(Config::$modSettings['lp_show_related_pages']))
			return;

		if (empty(Utils::$context['lp_page']['options']['show_related_pages']))
			return;

		Utils::$context['lp_page']['related_pages'] = $this->repository->getRelatedPages($page);
	}

	/**
	 * @throws IntlException
	 */
	private function prepareComments(): void
	{
		if ($this->getCommentBlockType() === '' || $this->getCommentBlockType() === 'none')
			return;

		if (empty(Utils::$context['lp_page']['options']['allow_comments']))
			return;

		Lang::load('Editor');

		$this->hook('comments');

		if (isset(Utils::$context['lp_' . Config::$modSettings['lp_show_comment_block'] . '_comment_block']))
			return;

		$this->prepareJsonData();

		(new Comment(Utils::$context['lp_page']['alias']))->show();
	}

	private function prepareJsonData(): void
	{
		$txtData = [
			'pages'         => Lang::$txt['pages'],
			'author'        => Lang::$txt['author'],
			'reply'         => Lang::$txt['reply'],
			'modify'        => Lang::$txt['modify'],
			'modify_cancel' => Lang::$txt['modify_cancel'],
			'remove'        => Lang::$txt['remove'],
			'add_comment'   => Lang::$txt['lp_comment_placeholder'],
			'post'          => Lang::$txt['post'],
			'save'          => Lang::$txt['save'],
			'title'         => Lang::$txt['lp_comments_title'],
			'prev'          => Lang::$txt['prev'],
			'next'          => Lang::$txt['next'],
			'bold'          => Lang::$editortxt['bold'],
			'italic'        => Lang::$editortxt['italic'],
			'quote'         => Lang::$editortxt['insert_quote'],
			'code'          => Lang::$editortxt['code'],
			'link'          => Lang::$editortxt['insert_link'],
			'image'         => Lang::$editortxt['insert_image'],
			'list'          => Lang::$editortxt['bullet_list'],
			'task_list'     => Lang::$txt['lp_task_list'],
		];

		$pageUrl = Utils::$context['lp_page']['url'];

		// @TODO Need to improve this case
		if (class_exists('\SimpleSEF')) {
			$pageUrl = (new \SimpleSEF())->getSefUrl($pageUrl);
		}

		$contextData = [
			'locale'  => Lang::$txt['lang_dictionary'],
			'pageUrl' => $pageUrl,
			'charset' => Utils::$context['character_set'],
		];

		$settingsData = [
			'lp_comment_sorting' => Config::$modSettings['lp_comment_sorting'] ?? '0',
		];

		Utils::$context['lp_json'] = json_encode([
			'txt'      => $txtData,
			'context'  => $contextData,
			'settings' => $settingsData,
			'icons'    => Icon::all(),
			'user'     => Utils::$context['user'],
		]);
	}

	private function updateNumViews(): void
	{
		if (empty(Utils::$context['lp_page']['id']) || User::$info['possibly_robot'])
			return;

		if (
			$this->session('lp')->isEmpty('last_page_viewed')
			|| $this->session('lp')->get('last_page_viewed') !== Utils::$context['lp_page']['id']
		) {
			$this->repository->updateNumViews(Utils::$context['lp_page']['id']);

			$this->session('lp')->put('last_page_viewed', Utils::$context['lp_page']['id']);
		}
	}
}
