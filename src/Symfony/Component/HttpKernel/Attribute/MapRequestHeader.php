<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Attribute;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestHeaderValueResolver;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class MapRequestHeader extends ValueResolver
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly int $validationFailedStatusCode = Response::HTTP_BAD_REQUEST,
        string $resolver = RequestHeaderValueResolver::class,
    ) {
        parent::__construct($resolver);
    }
}
