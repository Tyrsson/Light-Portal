<?php

declare(strict_types = 1);

/**
 * BlockImport.php
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2022 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @version 2.0
 */

namespace Bugo\LightPortal\Impex;

use Bugo\LightPortal\Helper;

if (! defined('SMF'))
	die('No direct access...');

final class BlockImport extends AbstractImport
{
	public function main()
	{
		global $context, $txt, $scripturl;

		loadTemplate('LightPortal/ManageImpex');

		$context['page_title']      = $txt['lp_portal'] . ' - ' . $txt['lp_blocks_import'];
		$context['page_area_title'] = $txt['lp_blocks_import'];
		$context['canonical_url']   = $scripturl . '?action=admin;area=lp_blocks;sa=import';

		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title'       => LP_NAME,
			'description' => $txt['lp_blocks_import_description']
		);

		$context['sub_template'] = 'manage_import';

		$this->run();
	}

	protected function run()
	{
		global $db_temp_cache, $db_cache, $smcFunc, $context, $txt;

		if (empty($file = Helper::file('import_file')->get()))
			return;

		// Might take some time.
		@set_time_limit(600);

		// Don't allow the cache to get too full
		$db_temp_cache = $db_cache;
		$db_cache = [];

		if ($file['type'] !== 'text/xml')
			return;

		$xml = simplexml_load_file($file['tmp_name']);

		if ($xml === false)
			return;

		if (! isset($xml->blocks->item[0]['block_id']))
			fatal_lang_error('lp_wrong_import_file', false);

		$items = $titles = $params = [];

		foreach ($xml as $element) {
			foreach ($element->item as $item) {
				$items[] = [
					'block_id'      => $block_id = intval($item['block_id']),
					'user_id'       => intval($item['user_id']),
					'icon'          => $item->icon,
					'type'          => str_replace('md', 'markdown', $item->type),
					'note'          => $item->note,
					'content'       => $item->content,
					'placement'     => $item->placement,
					'priority'      => intval($item['priority']),
					'permissions'   => intval($item['permissions']),
					'status'        => intval($item['status']),
					'areas'         => $item->areas,
					'title_class'   => strpos((string) $item->title_class, 'div.') !== false ? 'cat_bar' : $item->title_class,
					'title_style'   => $item->title_style,
					'content_class' => strpos((string) $item->content_class, 'div.') !== false ? 'roundframe' : $item->content_class,
					'content_style' => $item->content_style
				];

				if (! empty($item->titles)) {
					foreach ($item->titles as $title) {
						foreach ($title as $k => $v) {
							$titles[] = [
								'item_id' => $block_id,
								'type'    => 'block',
								'lang'    => $k,
								'title'   => $v
							];
						}
					}
				}

				if (! empty($item->params)) {
					foreach ($item->params as $param) {
						foreach ($param as $k => $v) {
							$params[] = [
								'item_id' => $block_id,
								'type'    => 'block',
								'name'    => $k,
								'value'   => $v
							];
						}
					}
				}
			}
		}

		$smcFunc['db_transaction']('begin');

		if (! empty($items)) {
			$context['import_successful'] = count($items);
			$items = array_chunk($items, 100);
			$count = sizeof($items);

			for ($i = 0; $i < $count; $i++) {
				$results = $smcFunc['db_insert']('replace',
					'{db_prefix}lp_blocks',
					array(
						'block_id'      => 'int',
						'user_id'       => 'int',
						'icon'          => 'string',
						'type'          => 'string',
						'note'          => 'string',
						'content'       => 'string-65534',
						'placement'     => 'string-10',
						'priority'      => 'int',
						'permissions'   => 'int',
						'status'        => 'int',
						'areas'         => 'string',
						'title_class'   => 'string',
						'title_style'   => 'string',
						'content_class' => 'string',
						'content_style' => 'string'
					),
					$items[$i],
					array('block_id'),
					2
				);

				$smcFunc['lp_num_queries']++;
			}
		}

		if (! empty($titles) && ! empty($results)) {
			$titles = array_chunk($titles, 100);
			$count  = sizeof($titles);

			for ($i = 0; $i < $count; $i++) {
				$results = $smcFunc['db_insert']('replace',
					'{db_prefix}lp_titles',
					array(
						'item_id' => 'int',
						'type'    => 'string',
						'lang'    => 'string',
						'title'   => 'string'
					),
					$titles[$i],
					array('item_id', 'type', 'lang'),
					2
				);

				$smcFunc['lp_num_queries']++;
			}
		}

		if (! empty($params) && ! empty($results)) {
			$params = array_chunk($params, 100);
			$count  = sizeof($params);

			for ($i = 0; $i < $count; $i++) {
				$results = $smcFunc['db_insert']('replace',
					'{db_prefix}lp_params',
					array(
						'item_id' => 'int',
						'type'    => 'string',
						'name'    => 'string',
						'value'   => 'string'
					),
					$params[$i],
					array('item_id', 'type', 'name'),
					2
				);

				$smcFunc['lp_num_queries']++;
			}
		}

		if (empty($results)) {
			$smcFunc['db_transaction']('rollback');
			fatal_lang_error('lp_import_failed', false);
		}

		$smcFunc['db_transaction']('commit');

		$context['import_successful'] = sprintf($txt['lp_import_success'], Helper::getSmartContext('lp_blocks_set', ['blocks' => $context['import_successful']]));

		// Restore the cache
		$db_cache = $db_temp_cache;

		Helper::cache()->flush();
	}
}
