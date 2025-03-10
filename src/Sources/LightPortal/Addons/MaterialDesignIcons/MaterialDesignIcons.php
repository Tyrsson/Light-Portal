<?php

/**
 * MaterialDesignIcons
 *
 * @package MaterialDesignIcons (Light Portal)
 * @link https://custom.simplemachines.org/index.php?mod=4244
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023-2024 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @category addon
 * @version 21.03.24
 */

namespace Bugo\LightPortal\Addons\MaterialDesignIcons;

use Bugo\Compat\Theme;
use Bugo\LightPortal\Addons\Plugin;

if (! defined('LP_NAME'))
	die('No direct access...');

/**
 * Generated by PluginMaker
 */
class MaterialDesignIcons extends Plugin
{
	public string $type = 'icons';

	private string $prefix = 'mdi mdi-';

	public function init(): void
	{
		Theme::loadCSSFile(
			'https://cdn.jsdelivr.net/npm/@mdi/font@7/css/materialdesignicons.min.css',
			[
				'external' => true,
				'seed'     => false,
			]
		);
	}

	public function prepareIconList(array &$icons): void
	{
		if (($mdIcons = $this->cache()->get('all_md_icons', 30 * 24 * 60 * 60)) === null) {
			$content = file_get_contents('https://raw.githubusercontent.com/Templarian/MaterialDesign/master/meta.json');
			$json = json_decode($content);

			$mdIcons = [];
			foreach ($json as $icon) {
				$mdIcons[] = $this->prefix . $icon->name;
			}

			$this->cache()->put('all_md_icons', $mdIcons, 30 * 24 * 60 * 60);
		}

		$icons = array_merge($icons, $mdIcons);
	}

	public function credits(array &$links): void
	{
		$links[] = [
			'title' => 'Material Design Icons',
			'link' => 'https://pictogrammers.com/library/mdi/',
			'author' => 'Pictogrammers',
			'license' => [
				'name' => 'Pictogrammers Free License',
				'link' => 'https://pictogrammers.com/docs/general/license/'
			]
		];
	}
}
