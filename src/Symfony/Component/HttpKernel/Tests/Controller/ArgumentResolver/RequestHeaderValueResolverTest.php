<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests\Controller\ArgumentResolver;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestHeader;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestHeaderValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RequestHeaderValueResolverTest extends TestCase
{
    public static function provideHeaderValueWithStringType(): iterable
    {
        yield 'with accept' => ['accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'];
        yield 'with accept-language' => ['accept-language', 'en-us,en;q=0.5'];
        yield 'with host' => ['host', 'localhost'];
        yield 'with user-agent' => ['user-agent', 'Symfony'];
    }

    public static function provideHeaderValueWithArrayType(): iterable
    {
        yield 'with accept' => [
            'accept',
            'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            [
                [
                    'text/html',
                    'application/xhtml+xml',
                    'application/xml',
                    '*/*',
                ],
            ],
        ];
        yield 'with accept-language' => [
            'accept-language',
            'en-us,en;q=0.5',
            [
                [
                    'en_US',
                    'en',
                ],
            ],
        ];
        yield 'with host' => [
            'host',
            'localhost',
            [
                ['localhost'],
            ],
        ];
        yield 'with user-agent' => [
            'user-agent',
            'Symfony',
            [
                ['Symfony'],
            ],
        ];
    }

    public static function provideHeaderValueWithAcceptHeaderType(): iterable
    {
        yield 'with accept' => [
            'accept',
            'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            [AcceptHeader::fromString('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')],
        ];
        yield 'with accept-language' => [
            'accept-language',
            'en-us,en;q=0.5',
            [AcceptHeader::fromString('en-us,en;q=0.5')],
        ];
        yield 'with host' => [
            'host',
            'localhost',
            [AcceptHeader::fromString('localhost')],
        ];
        yield 'with user-agent' => [
            'user-agent',
            'Symfony',
            [AcceptHeader::fromString('Symfony')],
        ];
    }

    public static function provideHeaderValueWithDefaultAndNull(): iterable
    {
        yield 'with hasDefaultValue' => [true, 'foo', false, 'foo'];
        yield 'with no isNullable' => [false, null, true, null];
    }

    public function testWrongType()
    {
        self::expectException(\LogicException::class);

        $metadata = new ArgumentMetadata('accept', 'int', false, false, null, false, [
            MapRequestHeader::class => new MapRequestHeader(),
        ]);

        $request = Request::create('/');

        $resolver = new RequestHeaderValueResolver();
        $resolver->resolve($request, $metadata);
    }

    /**
     * @dataProvider provideHeaderValueWithStringType
     */
    public function testWithStringType(string $parameter, string $value)
    {
        $resolver = new RequestHeaderValueResolver();

        $metadata = new ArgumentMetadata('variableName', 'string', false, false, null, false, [
            MapRequestHeader::class => new MapRequestHeader($parameter),
        ]);

        $request = Request::create('/');
        $request->headers->set($parameter, $value);

        $arguments = $resolver->resolve($request, $metadata);

        self::assertEquals([$value], $arguments);
    }

    /**
     * @dataProvider provideHeaderValueWithArrayType
     */
    public function testWithArrayType(string $parameter, string $value, array $expected)
    {
        $resolver = new RequestHeaderValueResolver();

        $metadata = new ArgumentMetadata('variableName', 'array', false, false, null, false, [
            MapRequestHeader::class => new MapRequestHeader($parameter),
        ]);

        $request = Request::create('/');
        $request->headers->set($parameter, $value);

        $arguments = $resolver->resolve($request, $metadata);

        self::assertEquals($expected, $arguments);
    }

    /**
     * @dataProvider provideHeaderValueWithAcceptHeaderType
     */
    public function testWithAcceptHeaderType(string $parameter, string $value, array $expected)
    {
        $resolver = new RequestHeaderValueResolver();

        $metadata = new ArgumentMetadata('variableName', AcceptHeader::class, false, false, null, false, [
            MapRequestHeader::class => new MapRequestHeader($parameter),
        ]);

        $request = Request::create('/');
        $request->headers->set($parameter, $value);

        $arguments = $resolver->resolve($request, $metadata);

        self::assertEquals($expected, $arguments);
    }

    /**
     * @dataProvider provideHeaderValueWithDefaultAndNull
     */
    public function testWithDefaultValueAndNull(bool $hasDefaultValue, ?string $defaultValue, bool $isNullable, ?string $expected)
    {
        $metadata = new ArgumentMetadata('wrong-header', 'string', false, $hasDefaultValue, $defaultValue, $isNullable, [
            MapRequestHeader::class => new MapRequestHeader(),
        ]);

        $request = Request::create('/');

        $resolver = new RequestHeaderValueResolver();
        $arguments = $resolver->resolve($request, $metadata);

        self::assertEquals([$expected], $arguments);
    }

    public function testWithNoDefaultAndNotNullable()
    {
        self::expectException(HttpException::class);
        self::expectExceptionMessage('Missing header "variableName".');

        $metadata = new ArgumentMetadata('variableName', 'string', false, false, null, false, [
            MapRequestHeader::class => new MapRequestHeader(),
        ]);

        $resolver = new RequestHeaderValueResolver();
        $resolver->resolve(Request::create('/'), $metadata);
    }
}
