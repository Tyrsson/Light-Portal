<?php declare(strict_types=1);

/**
 * FrontPage.php
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2024 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @version 2.5
 */

namespace Bugo\LightPortal\Actions;

use Bugo\LightPortal\Articles\{ArticleInterface, BoardArticle, ChosenPageArticle};
use Bugo\LightPortal\Articles\{ChosenTopicArticle, PageArticle, TopicArticle};
use Bugo\LightPortal\Helper;
use Bugo\LightPortal\Utils\{Config, ErrorHandler, Lang, Theme, Utils};
use Exception;
use IntlException;
use Latte\{Engine, Essential\RawPhpExtension, Loaders\FileLoader, Runtime\Html, RuntimeException};

final class FrontPage
{
	use Helper;

	private array $modes = [
		'all_pages'     => PageArticle::class,
		'all_topics'    => TopicArticle::class,
		'chosen_boards' => BoardArticle::class,
		'chosen_pages'  => ChosenPageArticle::class,
		'chosen_topics' => ChosenTopicArticle::class,
	];

	/**
	 * @throws IntlException
	 */
	public function show()
	{
		$this->middleware('light_portal_view');

		$this->hook('frontModes', [&$this->modes]);

		if (array_key_exists(Config::$modSettings['lp_frontpage_mode'], $this->modes))
			$this->prepare(new $this->modes[Config::$modSettings['lp_frontpage_mode']]);
		elseif (Config::$modSettings['lp_frontpage_mode'] === 'chosen_page')
			return $this->callHelper([new Page, 'show']);

		Utils::$context['lp_frontpage_num_columns'] = $this->getNumColumns();

		Utils::$context['canonical_url'] = Config::$scripturl;

		Utils::$context['page_title'] = Config::$modSettings['lp_frontpage_title'] ?: (Utils::$context['forum_name'] . ' - ' . Lang::$txt['lp_portal']);
		Utils::$context['linktree'][] = [
			'name'        => Lang::$txt['lp_portal'],
			'extra_after' => '(' . $this->translate('lp_articles_set', ['articles' => Utils::$context['total_articles']]) . ')'
		];

		$this->prepareTemplates();

		return false;
	}

	/**
	 * @throws IntlException
	 */
	public function prepare(ArticleInterface $article): void
	{
		$start = (int) $this->request('start');
		$limit = (int) Config::$modSettings['lp_num_items_per_page'] ?? 12;

		$article->init();

		if (($data = $this->cache()->get('articles_u' . Utils::$context['user']['id'] . '_' . $start . '_' . $limit)) === null) {
			$data['total'] = $article->getTotalCount();

			$this->updateStart($data['total'], $start, $limit);

			$data['articles'] = $article->getData($start, $limit);

			$this->cache()->put('articles_u' . Utils::$context['user']['id'] . '_' . $start . '_' . $limit, $data);
		}

		[$articles, $itemsCount] = [$data['articles'], $data['total']];

		Utils::$context['total_articles'] = $itemsCount;

		$articles = $this->postProcess($article, $articles);

		$this->preLoadImages($articles);

		Utils::$context['page_index'] = $this->constructPageIndex(LP_BASE_URL, $this->request()->get('start'), $itemsCount, $limit);
		Utils::$context['start'] = $this->request()->get('start');

		if (! empty(Config::$modSettings['lp_use_simple_pagination']))
			Utils::$context['page_index'] = $this->simplePaginate(LP_BASE_URL, $itemsCount, $limit);

		Utils::$context['portal_next_page'] = $this->request('start') + $limit < $itemsCount ? LP_BASE_URL . ';start=' . ($this->request('start') + $limit) : '';
		Utils::$context['lp_frontpage_articles'] = $articles;

		$this->hook('frontAssets');
	}

	public function prepareTemplates(): void
	{
		if (empty(Utils::$context['lp_frontpage_articles'])) {
			Utils::$context['sub_template'] = 'empty';
		} else {
			Utils::$context['sub_template'] = empty(Config::$modSettings['lp_frontpage_layout']) ? 'wrong_template' : 'layout';
		}

		Utils::$context['lp_frontpage_layouts'] = $this->getLayouts();

		$this->prepareLayoutSwitcher();

		// Mod authors can use their own logic here
		$this->hook('frontLayouts');

		$this->view(Config::$modSettings['lp_frontpage_layout']);
	}

	public function prepareLayoutSwitcher(): void
	{
		if (empty(Config::$modSettings['lp_show_layout_switcher']))
			return;

		Utils::$context['template_layers'][] = 'layout_switcher';

		if ($this->session()->isEmpty('lp_frontpage_layout')) {
			Utils::$context['lp_current_layout'] = $this->request('layout', Config::$modSettings['lp_frontpage_layout'] ?? 'default.latte');
		} else {
			Utils::$context['lp_current_layout'] = $this->request('layout', $this->session()->get('lp_frontpage_layout'));
		}

		$this->session()->put('lp_frontpage_layout', Utils::$context['lp_current_layout']);

		Config::$modSettings['lp_frontpage_layout'] = $this->session()->get('lp_frontpage_layout');
	}

	public function getLayouts(): array
	{
		Theme::loadTemplate('LightPortal/ViewFrontPage');

		$layouts = glob(Theme::$current->settings['default_theme_dir'] . '/LightPortal/layouts/*.latte');

		$extensions = ['.latte'];

		// Mod authors can add custom extensions for layouts
		$this->hook('customLayoutExtensions', [&$extensions]);

		foreach ($extensions as $extension) {
			$layouts = array_merge($layouts, glob(Theme::$current->settings['default_theme_dir'] . '/portal_layouts/*' . $extension));
		}

		$values = $titles = [];

		foreach ($layouts as $layout) {
			$values[] = $title = basename($layout);

			$shortName = ucfirst(strstr($title, '.', true) ?: $title);

			$titles[] = $title === 'default.latte' ? Lang::$txt['lp_default'] : str_replace('_', ' ', $shortName);
		}

		$layouts = array_combine($values, $titles);
		$default = $layouts['default.latte'];
		unset($layouts['default.latte']);

		return array_merge(['default.latte' => $default], $layouts);
	}

	public function view(string $layout): void
	{
		if (empty($layout))
			return;

		$latte = new Engine;
		$latte->setTempDirectory(empty(Config::$modSettings['cache_enable']) ? null : sys_get_temp_dir());
		$latte->setLoader(new FileLoader(Theme::$current->settings['default_theme_dir'] . '/LightPortal/layouts/'));
		$latte->addExtension(new RawPhpExtension);
		$latte->addFunction('teaser', function (string $text, int $length = 150) use ($latte): string {
			$text = $latte->invokeFilter('stripHtml', [$text]);

			return $latte->invokeFilter('truncate', [$text, $length]);
		});
		$latte->addFunction('icon', function (string $name, string $title = '') use ($latte): Html {
			$icon = Utils::$context['lp_icon_set'][$name];

			if (empty($title)) {
				return new Html($icon);
			}

			return new Html(str_replace(' class=', ' title="' . $title . '" class=', $icon));
		});

		$params = [
			'txt'         => Lang::$txt,
			'context'     => Utils::$context,
			'modSettings' => Config::$modSettings,
		];

		ob_start();

		try {
			$latte->render($layout, $params);
		} catch (RuntimeException $e) {
			if (is_file(Theme::$current->settings['default_theme_dir'] . '/portal_layouts/' . $layout)) {
				$latte->setLoader(new FileLoader(Theme::$current->settings['default_theme_dir'] . '/portal_layouts/'));
				$latte->render($layout, $params);
			} else {
				ErrorHandler::fatal($e->getMessage());
			}
		} catch (Exception $e) {
			ErrorHandler::fatal($e->getMessage());
		}

		Utils::$context['lp_layout'] = ob_get_clean();
	}

	/**
	 * Get the number columns for the frontpage layout
	 *
	 * Получаем количество колонок для макета главной страницы
	 */
	public function getNumColumns(): int
	{
		$num_columns = 12;

		if (empty(Config::$modSettings['lp_frontpage_num_columns']))
			return $num_columns;

		return $num_columns / match (Config::$modSettings['lp_frontpage_num_columns']) {
			'1' => 2,
			'2' => 3,
			'3' => 4,
			default => 6,
		};
	}

	/**
	 * Get the sort condition for SQL
	 *
	 * Получаем условие сортировки для SQL
	 */
	public function getOrderBy(): string
	{
		$sorting_types = [
			'title;desc'       => 't.title DESC',
			'title'            => 't.title',
			'created;desc'     => 'p.created_at DESC',
			'created'          => 'p.created_at',
			'updated;desc'     => 'p.updated_at DESC',
			'updated'          => 'p.updated_at',
			'author_name;desc' => 'author_name DESC',
			'author_name'      => 'author_name',
			'num_views;desc'   => 'p.num_views DESC',
			'num_views'        => 'p.num_views'
		];

		Utils::$context['current_sorting'] = $this->request('sort', 'created;desc');

		return $sorting_types[Utils::$context['current_sorting']];
	}

	public function updateStart(int $total, int &$start, int $limit): void
	{
		if ($start >= $total) {
			Utils::sendHttpStatus(404);
			$start = (floor(($total - 1) / $limit) + 1) * $limit - $limit;
		}

		$start = (int) abs($start);
	}

	/**
	 * Post processing for articles
	 *
	 * Заключительная обработка статей
	 * @throws IntlException
	 */
	private function postProcess(ArticleInterface $article, array $articles): array
	{
		return array_map(function ($item) use ($article) {
			if (Utils::$context['user']['is_guest']) {
				$item['is_new'] = false;
				$item['views']['num'] = 0;
			}

			if (isset($item['date'])) {
				$item['datetime'] = date('Y-m-d', (int) $item['date']);
				$item['raw_date'] = $item['date'];
				$item['date']     = $this->getFriendlyTime((int) $item['date']);
			}

			$item['msg_link'] ??= $item['link'];

			if (empty($item['image']) && ! empty(Config::$modSettings['lp_image_placeholder']))
				$item['image'] = Config::$modSettings['lp_image_placeholder'];

			if (! empty($item['views']['num']))
				$item['views']['num'] = $this->getFriendlyNumber((int) $item['views']['num']);

			return $item;
		}, $articles);
	}

	private function preLoadImages(array $articles): void
	{
		$images = array_column($articles, 'image');

		foreach ($images as $image) {
			Utils::$context['html_headers'] .= "\n\t" . '<link rel="preload" as="image" href="' . $image . '">';
		}
	}

	/**
	 * Get a number in friendly format ("1K" instead "1000", etc)
	 *
	 * Получаем число в приятном глазу формате (для чисел более 10к)
	 */
	private function getFriendlyNumber(int $value = 0): string
	{
		if ($value < 10000)
			return (string) $value;

		$k   = 10 ** 3;
		$mil = 10 ** 6;
		$bil = 10 ** 9;

		if ($value >= $bil)
			return number_format($value / $bil, 1) . 'B';
		else if ($value >= $mil)
			return number_format($value / $mil, 1) . 'M';

		return number_format($value / $k, 1) . 'K';
	}

	private function simplePaginate(string $url, int $total, int $limit): string
	{
		$max_pages = (($total - 1) / $limit) * $limit;

		$prev = Utils::$context['start'] - $limit;

		$next = Utils::$context['start'] + $limit > $max_pages ? '' : Utils::$context['start'] + $limit;

		$paginate = '';

		if ($prev >= 0)
			$paginate .= "<a class=\"button\" href=\"$url;start=$prev\">" . Utils::$context['lp_icon_set']['arrow_left'] . ' ' . Lang::$txt['prev'] . "</a>";

		if ($next)
			$paginate .= "<a class=\"button\" href=\"$url;start=$next\">" . Lang::$txt['next'] . ' ' . Utils::$context['lp_icon_set']['arrow_right'] . "</a>";

		return $paginate;
	}
}
