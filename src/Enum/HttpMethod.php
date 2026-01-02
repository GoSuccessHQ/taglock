<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Enum;

defined( 'ABSPATH' ) || exit;

enum HttpMethod: string {
	case GET = 'GET';
	case POST = 'POST';
}
