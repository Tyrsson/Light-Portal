<?php

/**
 * Import.php
 *
 * @package EhPortal (Light Portal)
 * @link https://custom.simplemachines.org/index.php?mod=4244
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2020-2022 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @category addon
 * @version 17.12.21
 */

namespace Bugo\LightPortal\Addons\EhPortal;

use Bugo\LightPortal\Impex\AbstractOtherPageImport;
use Bugo\LightPortal\Helper;

class Import extends AbstractOtherPageImport
{
	public function main()
	{
		global $context, $txt, $scripturl;

		$context['page_title']      = $txt['lp_portal'] . ' - ' . $txt['lp_eh_portal']['label_name'];
		$context['page_area_title'] = $txt['lp_pages_import'];
		$context['canonical_url']   = $scripturl . '?action=admin;area=lp_pages;sa=import_from_ep';

		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title'       => LP_NAME,
			'description' => $txt['lp_eh_portal']['desc']
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
						'default' => 'id_page',
						'reverse' => 'id_page DESC'
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
						'default' => 'namespace DESC',
						'reverse' => 'namespace'
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
						'default' => 'title DESC',
						'reverse' => 'title'
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
						<input type="submit" name="import_selection" value="' . $txt['lp_eh_portal']['button_run'] . '" class="button">
						<input type="submit" name="import_all" value="' . $txt['lp_eh_portal']['button_all'] . '" class="button">'
				)
			)
		);

		Helper::require('Subs-List');
		createList($listOptions);

		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'lp_pages';
	}

	public function getAll(int $start = 0, int $items_per_page = 0, string $sort = 'id_page'): array
	{
		global $smcFunc, $db_prefix, $user_info;

		db_extend();

		if (empty($smcFunc['db_list_tables'](false, $db_prefix . 'sp_pages')))
			return [];

		$request = $smcFunc['db_query']('', '
			SELECT id_page, namespace, title, body, type, permission_set, groups_allowed, views, status
			FROM {db_prefix}sp_pages
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
			$items[$row['id_page']] = array(
				'id'         => $row['id_page'],
				'alias'      => $row['namespace'],
				'type'       => $row['type'],
				'status'     => $row['status'],
				'num_views'  => $row['views'],
				'author_id'  => $user_info['id'],
				'created_at' => Helper::getFriendlyTime(time()),
				'title'      => $row['title']
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

		if (empty($smcFunc['db_list_tables'](false, $db_prefix . 'sp_pages')))
			return 0;

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}sp_pages',
			array()
		);

		[$num_pages] = $smcFunc['db_fetch_row']($request);

		$smcFunc['db_free_result']($request);
		$smcFunc['lp_num_queries']++;

		return (int) $num_pages;
	}

	protected function getItems(array $pages): array
	{
		global $smcFunc, $user_info;

		$request = $smcFunc['db_query']('', '
			SELECT id_page, namespace, title, body, type, permission_set, groups_allowed, views, status
			FROM {db_prefix}sp_pages' . (empty($pages) ? '' : '
			WHERE id_page IN ({array_int:pages})'),
			array(
				'pages' => $pages
			)
		);

		$items = [];
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			if (! empty($row['permission_set'])) {
				$perm = $row['permission_set'];
			} else {
				$groups = $row['groups_allowed'];

				if ($groups == -1) {
					$perm = 1;
				} elseif ($groups == 0) {
					$perm = 2;
				} elseif ($groups == 1) {
					$perm = 0;
				} else {
					$perm = 3;
				}
			}

			$items[$row['id_page']] = array(
				'page_id'      => $row['id_page'],
				'author_id'    => $user_info['id'],
				'alias'        => $row['namespace'] ?: ('page_' . $row['id_page']),
				'description'  => '',
				'content'      => $row['body'],
				'type'         => $row['type'],
				'permissions'  => $perm,
				'status'       => $row['status'],
				'num_views'    => $row['views'],
				'num_comments' => 0,
				'created_at'   => time(),
				'updated_at'   => 0,
				'title'        => $row['title']
			);
		}

		$smcFunc['db_free_result']($request);
		$smcFunc['lp_num_queries']++;

		return $items;
	}
}
