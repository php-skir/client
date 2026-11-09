<?php

declare(strict_types=1);

namespace Skir\Client;

enum WireFormat: string
{
    case Dense = 'dense';
    case Readable = 'readable';
}
