<?php declare(strict_types=1);

/**
 * PageArticle.php
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2024 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @version 2.6
 */

namespace Bugo\LightPortal\Articles;

use Bugo\Compat\{BBCodeParser, Config, Db, Lang, User, Utils};
use Bugo\LightPortal\Actions\PageInterface;
use Bugo\LightPortal\Actions\PageListInterface;
use Bugo\LightPortal\Utils\Content;

if (! defined('SMF'))
	die('No direct access...');

class PageArticle extends AbstractArticle
{
	protected array $selectedCategories = [];

	protected int $sorting = 0;

	public function init(): void
	{
		$this->selectedCategories = empty(Config::$modSettings['lp_frontpage_categories'])
			? [] : explode(',', Config::$modSettings['lp_frontpage_categories']);

		if (empty($this->selectedCategories) && $this->isFrontpageMode('all_pages')) {
			$this->selectedCategories = [0];
		}

		$this->sorting = (int) (Config::$modSettings['lp_frontpage_article_sorting'] ?? 0);

		$this->params = [
			'lang'                => User::$info['language'],
			'fallback_lang'       => Config::$language,
			'status'              => PageInterface::STATUS_ACTIVE,
			'current_time'        => time(),
			'permissions'         => $this->getPermissions(),
			'selected_categories' => $this->selectedCategories,
		];

		$this->orders = [
			'CASE WHEN com.created_at > 0 THEN 0 ELSE 1 END, comment_date DESC',
			'p.created_at DESC',
			'p.created_at',
			'date DESC',
		];

		$this->hook('frontPages', [
			&$this->columns, &$this->tables, &$this->params, &$this->wheres, &$this->orders
		]);
	}

	public function getData(int $start, int $limit): array
	{
		$titles = $this->getEntityData('title');

		$this->params += [
			'start' => $start,
			'limit' => $limit,
		];

		$result = Db::$db->query('', /** @lang text */ '
			SELECT
				p.page_id, p.category_id, p.author_id, p.alias, p.content, p.description, p.type, p.status, p.num_views,
				CASE WHEN COALESCE(par.value, \'0\') != \'0\' THEN p.num_comments ELSE 0 END AS num_comments, p.created_at,
				GREATEST(p.created_at, p.updated_at) AS date, COALESCE(t.title, tf.title) AS cat_title, mem.real_name AS author_name,
				cat.icon as cat_icon, com.created_at AS comment_date, com.author_id AS comment_author_id, mem2.real_name AS comment_author_name,
				com.message AS comment_message' . (empty($this->columns) ? '' : ', ' . implode(', ', $this->columns)) . '
			FROM {db_prefix}lp_pages AS p
				LEFT JOIN {db_prefix}lp_categories AS cat ON (cat.category_id = p.category_id)
				LEFT JOIN {db_prefix}members AS mem ON (p.author_id = mem.id_member)
				LEFT JOIN {db_prefix}lp_comments AS com ON (p.last_comment_id = com.id)
				LEFT JOIN {db_prefix}members AS mem2 ON (com.author_id = mem2.id_member)
				LEFT JOIN {db_prefix}lp_titles AS t ON (
					cat.category_id = t.item_id AND t.type = {literal:category} AND t.lang = {string:lang}
				)
				LEFT JOIN {db_prefix}lp_titles AS tf ON (
					cat.category_id = tf.item_id AND tf.type = {literal:category} AND tf.lang = {string:fallback_lang}
				)
				LEFT JOIN {db_prefix}lp_params AS par ON (
					par.item_id = com.page_id AND par.type = {literal:page} AND par.name = {literal:allow_comments}
				)' . (empty($this->tables) ? '' : '
				' . implode("\n\t\t\t\t\t", $this->tables)) . '
			WHERE p.status = {int:status}
				AND p.created_at <= {int:current_time}
				AND p.permissions IN ({array_int:permissions})' . (empty($this->selectedCategories) ? '' : '
				AND p.category_id IN ({array_int:selected_categories})') . (empty($this->wheres) ? '' : '
				' . implode("\n\t\t\t\t\t", $this->wheres)) . '
			ORDER BY ' . (empty(Config::$modSettings['lp_frontpage_order_by_replies']) ? '' : 'num_comments DESC, ')
				. $this->orders[$this->sorting] . '
			LIMIT {int:start}, {int:limit}',
			$this->params,
		);

		$pages = [];
		while ($row = Db::$db->fetch_assoc($result)) {
			if (! isset($pages[$row['page_id']])) {
				$row['content'] = Content::parse($row['content'], $row['type']);

				$pages[$row['page_id']] = [
					'id'        => (int) $row['page_id'],
					'section'   => $this->getSectionData($row),
					'author'    => $this->getAuthorData($row),
					'date'      => $this->getDate($row),
					'title'     => $this->getTitle($titles, $row),
					'link'      => LP_PAGE_URL . $row['alias'],
					'views'     => $this->getViewsData($row),
					'replies'   => $this->getRepliesData($row),
					'is_new'    => $this->isNew($row),
					'image'     => $this->getImage($row),
					'can_edit'  => $this->canEdit($row),
					'edit_link' => $this->getEditLink($row),
				];
			}

			$this->prepareTeaser($pages, $row);

			$this->hook('frontPagesOutput', [&$pages, $row]);
		}

		Db::$db->free_result($result);

		$this->prepareTags($pages);

		return $this->getItemsWithUserAvatars($pages);
	}

	public function getTotalCount(): int
	{
		$result = Db::$db->query('', /** @lang text */ '
			SELECT COUNT(p.page_id)
			FROM {db_prefix}lp_pages AS p' . (empty($this->tables) ? '' : '
				' . implode("\n\t\t\t\t\t", $this->tables)) . '
			WHERE p.status = {int:status}
				AND p.created_at <= {int:current_time}
				AND p.permissions IN ({array_int:permissions})' . (empty($this->selectedCategories) ? '' : '
				AND p.category_id IN ({array_int:selected_categories})') . (empty($this->wheres) ? '' : '
				' . implode("\n\t\t\t\t\t", $this->wheres)),
			$this->params,
		);

		[$count] = Db::$db->fetch_row($result);

		Db::$db->free_result($result);

		return (int) $count;
	}

	private function getSectionData(array $row): array
	{
		return [
			'icon' => $this->getIcon($row['cat_icon']),
			'name' => empty($row['category_id']) ? '' : $row['cat_title'],
			'link' => empty($row['category_id']) ? '' : (LP_BASE_URL . ';sa=categories;id=' . $row['category_id']),
		];
	}

	private function getAuthorData(array $row): array
	{
		$authorId   = $row['author_id'];
		$authorName = $row['author_name'];

		if ($this->sorting === 0 && $row['num_comments']) {
			$authorId   = $row['comment_author_id'];
			$authorName = $row['comment_author_name'];
		}

		return [
			'id'   => (int) $authorId,
			'link' => Config::$scripturl . '?action=profile;u=' . $authorId,
			'name' => $authorName,
		];
	}

	private function getDate(array $row): int
	{
		if ($this->sorting === 0 && $row['comment_date']) {
			return (int) $row['comment_date'];
		}

		if ($this->sorting === 3) {
			return (int) $row['date'];
		}

		return (int) $row['created_at'];
	}

	private function getTitle(array $titles, array $row): string
	{
		return $this->getTranslatedTitle($titles[$row['page_id']]);
	}

	private function getViewsData(array $row): array
	{
		return [
			'num'   => (int) $row['num_views'],
			'title' => Lang::$txt['lp_views'],
			'after' => '',
		];
	}

	private function getRepliesData(array $row): array
	{
		return [
			'num'   => $this->getCommentBlockType() === 'default' ? (int) $row['num_comments'] : 0,
			'title' => Lang::$txt['lp_comments'],
			'after' => '',
		];
	}

	private function isNew(array $row): bool
	{
		return User::$info['last_login'] < $row['date'] && (int) $row['author_id'] !== User::$info['id'];
	}

	private function getImage(array $row): string
	{
		if (empty(Config::$modSettings['lp_show_images_in_articles']))
			return '';

		return $this->getImageFromText($row['content']);
	}

	private function canEdit(array $row): bool
	{
		return User::$info['is_admin']
			|| Utils::$context['allow_light_portal_manage_pages_any']
			|| (Utils::$context['allow_light_portal_manage_pages_own'] && (int) $row['author_id'] === User::$info['id']);
	}

	private function getEditLink(array $row): string
	{
		return Config::$scripturl . '?action=admin;area=lp_pages;sa=edit;id=' . $row['page_id'];
	}

	private function prepareTeaser(array &$pages, array $row): void
	{
		if (empty(Config::$modSettings['lp_show_teaser']))
			return;

		$pages[$row['page_id']]['teaser'] = $this->getTeaser(
			$this->sorting === 0 && $row['num_comments']
				? BBCodeParser::load()->parse($row['comment_message'])
				: ($row['description'] ?: $row['content'])
		);
	}

	private function prepareTags(array &$pages): void
	{
		if ($pages === [])
			return;

		$result = Db::$db->query('', '
			SELECT t.tag_id, t.icon, pt.page_id, COALESCE(tt.title, tf.title) AS title
			FROM {db_prefix}lp_tags AS t
				LEFT JOIN {db_prefix}lp_page_tags AS pt ON (t.tag_id = pt.tag_id)
				LEFT JOIN {db_prefix}lp_titles AS tt ON (
					pt.tag_id = tt.item_id AND tt.type = {literal:tag} AND tt.lang = {string:lang}
				)
				LEFT JOIN {db_prefix}lp_titles AS tf ON (
					pt.tag_id = tf.item_id AND tf.type = {literal:tag} AND tf.lang = {string:fallback_lang}
				)
			WHERE pt.page_id IN ({array_int:pages})
				AND t.status = {int:status}
			ORDER BY title',
			[
				'lang'          => User::$info['language'],
				'fallback_lang' => Config::$language,
				'pages'         => array_keys($pages),
				'status'        => PageListInterface::STATUS_ACTIVE,
			]
		);

		while ($row = Db::$db->fetch_assoc($result)) {
			$pages[$row['page_id']]['tags'][] = [
				'icon'  => $this->getIcon($row['icon']),
				'title' => $row['title'],
				'href'  => LP_BASE_URL . ';sa=tags;id=' . $row['tag_id'],
			];
		}

		Db::$db->free_result($result);
	}
}
