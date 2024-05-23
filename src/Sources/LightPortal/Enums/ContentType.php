<?php declare(strict_types=1);

/**
 * ContentType.php
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2024 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @version 2.6
 */

namespace Bugo\LightPortal\Enums;

enum ContentType: string
{
	case BBC = 'bbc';
	case HTML = 'html';
	case PHP = 'php';
}
