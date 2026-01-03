<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Enum;

use function defined;

defined( 'ABSPATH' ) || exit;

enum HttpMethod: string {
	case GET = 'GET';
	case POST = 'POST';
	case PUT = 'PUT';
	case DELETE = 'DELETE';
}
