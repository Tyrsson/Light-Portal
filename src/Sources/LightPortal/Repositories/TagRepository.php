<?php declare(strict_types=1);

/**
 * TagRepository.php
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2024 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @version 2.6
 */

namespace Bugo\LightPortal\Repositories;

use Bugo\Compat\{Config, Database as Db, ErrorHandler};
use Bugo\Compat\{Security, User, Utils};

if (! defined('SMF'))
	die('No direct access...');

final class TagRepository extends AbstractRepository
{
	protected string $entity = 'tag';

	public function getAll(int $start, int $limit, string $sort): array
	{
		$result = Db::$db->query('', /** @lang text */ '
			SELECT tag.tag_id, tag.icon, tag.status, COALESCE(t.title, tf.title) AS tag_title
			FROM {db_prefix}lp_tags AS tag
				LEFT JOIN {db_prefix}lp_titles AS t ON (
					tag.tag_id = t.item_id AND t.type = {literal:tag} AND t.lang = {string:lang}
				)
				LEFT JOIN {db_prefix}lp_titles AS tf ON (
					tag.tag_id = tf.item_id AND tf.type = {literal:tag} AND tf.lang = {string:fallback_lang}
				)
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:limit}',
			[
				'lang'          => User::$info['language'],
				'fallback_lang' => Config::$language,
				'sort'          => $sort,
				'start'         => $start,
				'limit'         => $limit,
			]
		);

		$items = [];
		while ($row = Db::$db->fetch_assoc($result)) {
			$items[$row['tag_id']] = [
				'id'     => (int) $row['tag_id'],
				'icon'   => $this->getIcon($row['icon']),
				'status' => (int) $row['status'],
				'title'  => $row['tag_title'],
			];
		}

		Db::$db->free_result($result);
		Utils::$context['lp_num_queries']++;

		return $items;
	}

	public function getTotalCount(): int
	{
		$result = Db::$db->query('', /** @lang text */ '
			SELECT COUNT(tag_id)
			FROM {db_prefix}lp_tags',
			[]
		);

		[$count] = Db::$db->fetch_row($result);

		Db::$db->free_result($result);
		Utils::$context['lp_num_queries']++;

		return (int) $count;
	}

	public function getData(int $item): array
	{
		if ($item === 0)
			return [];

		$result = Db::$db->query('', '
			SELECT tag.tag_id, tag.icon, tag.status, t.lang, t.title
			FROM {db_prefix}lp_tags AS tag
				LEFT JOIN {db_prefix}lp_titles AS t ON (tag.tag_id = t.item_id AND t.type = {literal:tag})
			WHERE tag.tag_id = {int:item}',
			[
				'item' => $item,
			]
		);

		if (empty(Db::$db->num_rows($result))) {
			Utils::$context['error_link'] = Config::$scripturl . '?action=admin;area=lp_tags';

			ErrorHandler::fatalLang('lp_tag_not_found', status: 404);
		}

		while ($row = Db::$db->fetch_assoc($result)) {
			$data ??= [
				'id'     => (int) $row['tag_id'],
				'icon'   => $row['icon'],
				'status' => (int) $row['status'],
			];

			if (! empty($row['lang']))
				$data['titles'][$row['lang']] = $row['title'];
		}

		Db::$db->free_result($result);
		Utils::$context['lp_num_queries']++;

		return $data ?? [];
	}

	public function setData(int $item = 0): void
	{
		if (isset(Utils::$context['post_errors']) || (
			$this->request()->hasNot('save') &&
			$this->request()->hasNot('save_exit'))
		) {
			return;
		}

		Security::checkSubmitOnce('check');

		$this->prepareTitles();

		if (empty($item)) {
			Utils::$context['lp_tag']['titles'] = array_filter(Utils::$context['lp_tag']['titles']);
			$item = $this->addData();
		} else {
			$this->updateData($item);
		}

		$this->cache()->flush();

		if ($this->request()->has('save_exit'))
			Utils::redirectexit('action=admin;area=lp_tags;sa=main');

		if ($this->request()->has('save'))
			Utils::redirectexit('action=admin;area=lp_tags;sa=edit;id=' . $item);
	}

	public function remove(array $items): void
	{
		if ($items === [])
			return;

		Db::$db->query('', '
			DELETE FROM {db_prefix}lp_tags
			WHERE tag_id IN ({array_int:items})',
			[
				'items' => $items,
			]
		);

		Db::$db->query('', '
			DELETE FROM {db_prefix}lp_page_tags
			WHERE tag_id IN ({array_int:items})',
			[
				'items' => $items,
			]
		);

		Utils::$context['lp_num_queries'] += 2;

		$this->cache()->flush();
	}

	private function addData(): int
	{
		Db::$db->transaction('begin');

		$item = (int) Db::$db->insert('',
			'{db_prefix}lp_tags',
			[
				'icon'   => 'string-60',
				'status' => 'int',
			],
			[
				Utils::$context['lp_tag']['icon'],
				Utils::$context['lp_tag']['status'],
			],
			['tag_id'],
			1
		);

		Utils::$context['lp_num_queries']++;

		if (empty($item)) {
			Db::$db->transaction('rollback');
			return 0;
		}

		$this->saveTitles($item);

		Db::$db->transaction('commit');

		return $item;
	}

	private function updateData(int $item): void
	{
		Db::$db->transaction('begin');

		Db::$db->query('', '
			UPDATE {db_prefix}lp_tags
			SET icon = {string:icon}, status = {int:status}
			WHERE tag_id = {int:tag_id}',
			[
				'icon'   => Utils::$context['lp_tag']['icon'],
				'status' => Utils::$context['lp_tag']['status'],
				'tag_id' => $item,
			]
		);

		Utils::$context['lp_num_queries']++;

		$this->saveTitles($item, 'replace');

		Db::$db->transaction('commit');
	}

	private function prepareTitles(): void
	{
		// Remove all punctuation symbols
		Utils::$context['lp_tag']['titles'] = preg_replace(
			"#[[:punct:]]#", "", Utils::$context['lp_tag']['titles']
		);
	}
}
