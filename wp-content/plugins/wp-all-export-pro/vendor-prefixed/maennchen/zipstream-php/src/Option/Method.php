<?php

declare(strict_types=1);

namespace Soflyy\WpAllExportPro\Vendor\ZipStream\Option;

use Soflyy\WpAllExportPro\Vendor\MyCLabs\Enum\Enum;

/**
 * Methods enum
 *
 * @method static STORE(): Method
 * @method static DEFLATE(): Method
 * @psalm-immutable
 */
class Method extends Enum
{
    public const STORE = 0x00;

    public const DEFLATE = 0x08;
}
