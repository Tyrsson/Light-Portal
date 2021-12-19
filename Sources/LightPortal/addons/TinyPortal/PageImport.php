<?php

/**
 * PageImport.php
 *
 * @package TinyPortal (Light Portal)
 * @link https://custom.simplemachines.org/index.php?mod=4244
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2020-2022 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @category addon
 * @version 17.12.21
 */

namespace Bugo\LightPortal\Addons\TinyPortal;

use Bugo\LightPortal\Impex\AbstractOtherPageImport;
use Bugo\LightPortal\Helper;

class PageImport extends AbstractOtherPageImport
{
	public function main()
	{
		global $context, $txt, $scripturl;

		$context['page_title']      = $txt['lp_portal'] . ' - ' . $txt['lp_tiny_portal']['label_name'];
		$context['page_area_title'] = $txt['lp_pages_import'];
		$context['canonical_url']   = $scripturl . '?action=admin;area=lp_pages;sa=import_from_tp';

		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title'       => LP_NAME,
			'description' => $txt['lp_tiny_portal']['page_import_desc']
		);

		$this->run();

		$listOptions = array(
			'id' => 'lp_pages',
			'items_per_page' => 50,
			'title' => $txt['lp_pages_import'],
			'no_items_label' => $txt['lp_no_items'],
			'base_href' => $context['canonical_url'],
			'default_sort_col' => 'id',
			'get_items' => array(
				'function' => array($this, 'getAll')
			),
			'get_count' => array(
				'function' => array($this, 'getTotalCount')
			),
			'columns' => array(
				'id' => array(
					'header' => array(
						'value' => '#',
						'style' => 'width: 5%'
					),
					'data' => array(
						'db'    => 'id',
						'class' => 'centertext'
					),
					'sort' => array(
						'default' => 'id',
						'reverse' => 'id DESC'
					)
				),
				'alias' => array(
					'header' => array(
						'value' => $txt['lp_page_alias']
					),
					'data' => array(
						'db'    => 'alias',
						'class' => 'centertext word_break'
					),
					'sort' => array(
						'default' => 'shortname DESC',
						'reverse' => 'shortname'
					)
				),
				'title' => array(
					'header' => array(
						'value' => $txt['lp_title']
					),
					'data' => array(
						'db'    => 'title',
						'class' => 'word_break'
					),
					'sort' => array(
						'default' => 'subject DESC',
						'reverse' => 'subject'
					)
				),
				'actions' => array(
					'header' => array(
						'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" checked>'
					),
					'data' => array(
						'function' => fn($entry) => '<input type="checkbox" value="' . $entry['id'] . '" name="pages[]" checked>',
						'class' => 'centertext'
					)
				)
			),
			'form' => array(
				'href' => $context['canonical_url']
			),
			'additional_rows' => array(
				array(
					'position' => 'below_table_data',
					'value' => '
						<input type="hidden">
						<input type="submit" name="import_selection" value="' . $txt['lp_tiny_portal']['button_run'] . '" class="button">
						<input type="submit" name="import_all" value="' . $txt['lp_tiny_portal']['button_all'] . '" class="button">'
				)
			)
		);

		Helper::require('Subs-List');
		createList($listOptions);

		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'lp_pages';
	}

	public function getAll(int $start = 0, int $items_per_page = 0, string $sort = 'id'): array
	{
		global $smcFunc, $db_prefix;

		db_extend();

		if (empty($smcFunc['db_list_tables'](false, $db_prefix . 'tp_articles')))
			return [];

		$request = $smcFunc['db_query']('', '
			SELECT id, date, subject, author_id, off, views, shortname, type
			FROM {db_prefix}tp_articles
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:limit}',
			array(
				'sort'  => $sort,
				'start' => $start,
				'limit' => $items_per_page
			)
		);

		$items = [];
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$items[$row['id']] = array(
				'id'         => $row['id'],
				'alias'      => $row['shortname'],
				'type'       => $row['type'],
				'status'     => (int) empty($row['off']),
				'num_views'  => $row['views'],
				'author_id'  => $row['author_id'],
				'created_at' => Helper::getFriendlyTime($row['date']),
				'title'      => $row['subject']
			);
		}

		$smcFunc['db_free_result']($request);
		$smcFunc['lp_num_queries']++;

		return $items;
	}

	public function getTotalCount(): int
	{
		global $smcFunc, $db_prefix;

		db_extend();

		if (empty($smcFunc['db_list_tables'](false, $db_prefix . 'tp_articles')))
			return 0;

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}tp_articles',
			array()
		);

		[$num_pages] = $smcFunc['db_fetch_row']($request);

		$smcFunc['db_free_result']($request);
		$smcFunc['lp_num_queries']++;

		return (int) $num_pages;
	}

	protected function getItems(array $pages): array
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT a.id, a.date, a.body, a.intro, a.subject, a.author_id, a.off, a.options, a.comments, a.views, a.shortname, a.type, a.pub_start, a.pub_end, v.value3
			FROM {db_prefix}tp_articles AS a
				LEFT JOIN {db_prefix}tp_variables AS v ON (a.category = v.id AND v.type = {string:type})' . (empty($pages) ? '' : '
			WHERE a.id IN ({array_int:pages})'),
			array(
				'type'  => 'category',
				'pages' => $pages
			)
		);

		$items = [];
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$permissions = explode(',', $row['value3']);

			$perm = 0;
			if (count($permissions) == 1 && $permissions[0] == -1) {
				$perm = 1;
			} elseif (count($permissions) == 1 && $permissions[0] == 0) {
				$perm = 2;
			} elseif (in_array(-1, $permissions)) {
				$perm = 3;
			} elseif (in_array(0, $permissions)) {
				$perm = 3;
			}

			$items[$row['id']] = array(
				'page_id'      => $row['id'],
				'author_id'    => $row['author_id'],
				'alias'        => $row['shortname'] ?: ('page_' . $row['id']),
				'description'  => strip_tags(parse_bbc($row['intro'])),
				'content'      => $row['body'],
				'type'         => $row['type'],
				'permissions'  => $perm,
				'status'       => (int) empty($row['off']),
				'num_views'    => $row['views'],
				'num_comments' => 0,
				'created_at'   => $row['date'],
				'updated_at'   => 0,
				'subject'      => $row['subject'],
				'options'      => explode(',', $row['options'])
			);
		}

		$smcFunc['db_free_result']($request);
		$smcFunc['lp_num_queries']++;

		return $items;
	}
}
