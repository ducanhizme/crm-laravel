<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test; // Import the Test attribute

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    #[Test]
    public function that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
