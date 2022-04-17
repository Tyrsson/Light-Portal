<?php

/**
 * BootstrapIcons.php
 *
 * @package BootstrapIcons (Light Portal)
 * @link https://custom.simplemachines.org/index.php?mod=4244
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2021-2022 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @category addon
 * @version 16.04.22
 */

namespace Bugo\LightPortal\Addons\BootstrapIcons;

use Bugo\LightPortal\Addons\Plugin;

if (! defined('LP_NAME'))
	die('No direct access...');

/**
 * Generated by Plugin Maker
 */
class BootstrapIcons extends Plugin
{
	public string $type = 'icons';

	private string $prefix = 'bi bi-';

	public function init()
	{
		loadCSSFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1/font/bootstrap-icons.min.css', ['external' => true, 'seed' => false]);
	}

	public function addSettings(array &$config_vars)
	{
		$config_vars['bootstrap_icons'][] = ['check', 'replace_ui_icons'];
	}

	public function prepareIconList(array &$all_icons)
	{
		if (($icons = $this->cache()->get('all_bi_icons', 30 * 24 * 60 * 60)) === null) {
			$content = file_get_contents('https://raw.githubusercontent.com/twbs/icons/main/font/bootstrap-icons.json');
			$json = array_flip(smf_json_decode($content, true));

			$icons = [];
			foreach ($json as $icon) {
				$icons[] = $this->prefix . $icon;
			}

			$this->cache()->put('all_bi_icons', $icons, 30 * 24 * 60 * 60);
		}

		$all_icons = array_merge($all_icons, $icons);
	}

	public function changeIconSet(array &$set)
	{
		if (empty($this->context['lp_bootstrap_icons_plugin']['replace_ui_icons']))
			return;

		$set['access']        = 'key';
		$set['arrow_left']    = 'arrow-left';
		$set['arrow_right']   = 'arrow-right';
		$set['arrows']        = 'arrows-move';
		$set['big_image']     = 'image big_image';
		$set['bold']          = 'type-bold';
		$set['calendar']      = 'calendar-plus';
		$set['category']      = 'tablet-landscape';
		$set['chevron_right'] = 'arrow-right-circle-fill';
		$set['circle_dot']    = 'record-circle-fill';
		$set['circle']        = 'circle-fill';
		$set['close']         = 'x-lg';
		$set['code']          = 'code';
		$set['cog_spin']      = 'gear';
		$set['comments']      = 'chat-fill';
		$set['content']       = 'newspaper';
		$set['copyright']     = 'at';
		$set['date']          = 'clock';
		$set['design']        = 'fan';
		$set['donate']        = 'currency-dollar';
		$set['download']      = 'download';
		$set['edit']          = 'pencil-square';
		$set['export']        = 'file-arrow-down';
		$set['gear']          = 'gear-fill';
		$set['home']          = 'house-door';
		$set['image']         = 'image';
		$set['import']        = 'file-arrow-up';
		$set['info']          = 'exclamation-circle';
		$set['italic']        = 'type-italic';
		$set['link']          = 'link';
		$set['main']          = 'card-list';
		$set['map_signs']     = 'map';
		$set['pager']         = 'inboxes';
		$set['panels']        = 'columns-gap';
		$set['plus_circle']   = 'plus-circle-fill';
		$set['plus']          = 'plus';
		$set['preview']       = 'check-all';
		$set['quote']         = 'quote';
		$set['redirect']      = 'arrow-return-right';
		$set['remove']        = 'trash';
		$set['replies']       = 'chat-text-fill';
		$set['reply']         = 'reply';
		$set['save_exit']     = 'check-square';
		$set['save']          = 'check-circle';
		$set['search']        = 'search';
		$set['sections']      = 'folder';
		$set['sign_in_alt']   = 'box-arrow-in-right';
		$set['sign_out_alt']  = 'box-arrow-right';
		$set['simple']        = 'list';
		$set['sort']          = 'sort-numeric-down';
		$set['spider']        = 'robot';
		$set['submit']        = 'send';
		$set['tag']           = 'tag-fill';
		$set['tags']          = 'tags-fill';
		$set['tile']          = 'layout-split';
		$set['toggle']        = 'toggle-';
		$set['tools']         = 'tools';
		$set['undo']          = 'backspace';
		$set['user_plus']     = 'person-plus-fill';
		$set['user']          = 'person-fill';
		$set['users']         = 'people-fill';
		$set['views']         = 'eye-fill';
		$set['youtube']       = 'youtube';

		$set = array_map(fn($icon): string => $this->prefix . $icon, $set);
	}

	public function credits(array &$links)
	{
		$links[] = [
			'title' => 'Bootstrap Icons',
			'link' => 'https://github.com/twbs/icons',
			'author' => 'The Bootstrap Authors',
			'license' => [
				'name' => 'the MIT License',
				'link' => 'https://github.com/twbs/icons/blob/main/LICENSE.md'
			]
		];
	}
}
