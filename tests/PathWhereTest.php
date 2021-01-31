<?php

namespace Tomb1n0\GuzzleMockHandler\Tests;

use PHPUnit\Framework\TestCase;
use Tomb1n0\GuzzleMockHandler\PathWhere;

class PathWhereTest extends TestCase
{
    /** @test */
    public function matches_passes_for_regex()
    {
        $pw = new PathWhere('test', '.*', 'sam/something/{test}');

        $this->assertTrue($pw->matches('sam/something/asdsd'));
    }

    /** @test */
    public function matches_fails_for_regex_numbers_if_string_in_path()
    {
        $pw = new PathWhere('test', '[0-9]*', 'sam/something/{test}');

        $this->assertFalse($pw->matches('sam/somethingk/asdsd'));
    }
}
