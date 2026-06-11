<?php

declare(strict_types=1);

namespace PhpPdf\Shading\ShadingFunctions;

use PhpPdf\Shading\ShadingFunctions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(ShadingFunctions::class)]
#[CoversMethod(ShadingFunctions::class, '__construct')]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function classIsNotDirectlyInstantiable(): void
    {
        // Arrange
        $reflection = new ReflectionClass(ShadingFunctions::class);

        // Act
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);

        // Assert — the constructor is private, preventing direct instantiation
        self::assertTrue($constructor->isPrivate());
    }

    #[Test]
    public function privateConstructorCanBeInvokedViaReflection(): void
    {
        // Arrange
        $reflection = new ReflectionClass(ShadingFunctions::class);
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        $instance = $reflection->newInstanceWithoutConstructor();

        // Act / Assert — invoking the private constructor does not throw
        $constructor->invoke($instance);
        self::assertInstanceOf(ShadingFunctions::class, $instance);
    }
}
