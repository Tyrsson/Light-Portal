<?php declare(strict_types=1);

/**
 * BlockExport.php
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2023 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @version 2.4
 */

namespace Bugo\LightPortal\Areas\Export;

use Bugo\LightPortal\Repositories\BlockRepository;
use DomDocument;
use DOMException;

if (! defined('SMF'))
	die('No direct access...');

final class BlockExport extends AbstractExport
{
	private BlockRepository $repository;

	public function __construct()
	{
		$this->repository = new BlockRepository;
	}

	public function main(): void
	{
		$this->loadTemplate('LightPortal/ManageImpex', 'manage_export_blocks');

		$this->context['page_title']      = $this->txt['lp_portal'] . ' - ' . $this->txt['lp_blocks_export'];
		$this->context['page_area_title'] = $this->txt['lp_blocks_export'];
		$this->context['canonical_url']   = $this->scripturl . '?action=admin;area=lp_blocks;sa=export';

		$this->context[$this->context['admin_menu_name']]['tab_data'] = [
			'title'       => LP_NAME,
			'description' => $this->txt['lp_blocks_export_description']
		];

		$this->run();

		$this->context['lp_current_blocks'] = $this->repository->getAll();
	}

	protected function getData(): array
	{
		if ($this->request()->isEmpty('blocks') && $this->request()->hasNot('export_all'))
			return [];

		$blocks = $this->request('blocks') && $this->request()->hasNot('export_all') ? $this->request('blocks') : null;

		$result = $this->smcFunc['db_query']('', '
			SELECT
				b.block_id, b.icon, b.type, b.note, b.content, b.placement, b.priority, b.permissions, b.status, b.areas, b.title_class, b.content_class,
				pt.lang, pt.title, pp.name, pp.value
			FROM {db_prefix}lp_blocks AS b
				LEFT JOIN {db_prefix}lp_titles AS pt ON (b.block_id = pt.item_id AND pt.type = {literal:block})
				LEFT JOIN {db_prefix}lp_params AS pp ON (b.block_id = pp.item_id AND pp.type = {literal:block})' . (empty($blocks) ? '' : '
			WHERE b.block_id IN ({array_int:blocks})'),
			[
				'blocks' => $blocks
			]
		);

		$items = [];
		while ($row = $this->smcFunc['db_fetch_assoc']($result)) {
			$items[$row['block_id']] ??= [
				'block_id'      => $row['block_id'],
				'icon'          => $row['icon'],
				'type'          => $row['type'],
				'note'          => $row['note'],
				'content'       => $row['content'],
				'placement'     => $row['placement'],
				'priority'      => $row['priority'],
				'permissions'   => $row['permissions'],
				'status'        => $row['status'],
				'areas'         => $row['areas'],
				'title_class'   => $row['title_class'],
				'content_class' => $row['content_class'],
			];

			if ($row['lang'] && $row['title'])
				$items[$row['block_id']]['titles'][$row['lang']] = $row['title'];

			if ($row['name'] && $row['value'])
				$items[$row['block_id']]['params'][$row['name']] = $row['value'];
		}

		$this->smcFunc['db_free_result']($result);
		$this->context['lp_num_queries']++;

		return array_map(fn($item) => array_filter($item), $items);
	}

	protected function getFile(): string
	{
		if (empty($items = $this->getData()))
			return '';

		try {
			$xml = new DomDocument('1.0', 'utf-8');
			$root = $xml->appendChild($xml->createElement('light_portal'));

			$xml->formatOutput = true;

			$xmlElements = $root->appendChild($xml->createElement('blocks'));
			foreach ($items as $item) {
				$xmlElement = $xmlElements->appendChild($xml->createElement('item'));
				foreach ($item as $key => $val) {
					$xmlName = $xmlElement->appendChild(in_array($key, ['block_id', 'priority', 'permissions', 'status']) ? $xml->createAttribute($key) : $xml->createElement($key));

					if (in_array($key, ['titles', 'params'])) {
						foreach ($val as $k => $v) {
							$xmlTitle = $xmlName->appendChild($xml->createElement($k));
							$xmlTitle->appendChild($xml->createTextNode($v));
						}
					} elseif ($key == 'content') {
						$xmlName->appendChild($xml->createCDATASection($val));
					} else {
						$xmlName->appendChild($xml->createTextNode($val));
					}
				}
			}

			$file = sys_get_temp_dir() . '/lp_blocks_backup.xml';
			$xml->save($file);
		} catch (DOMException $e) {
			$this->logError('[LP] ' . $this->txt['lp_blocks_export'] . ': ' . $e->getMessage());
		}

		return $file ?? '';
	}
}
