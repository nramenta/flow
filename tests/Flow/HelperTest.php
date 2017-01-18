<?php

namespace Flow;

use PHPUnit_Framework_TestCase;

class HelperTest extends PHPUnit_Framework_TestCase
{
    public function test_abs()
    {
        $this->assertEquals(10, Helper::abs(-10));
    }

    public function test_bytes()
    {
        $this->assertEquals('100', Helper::bytes(100));
        $this->assertEquals('1 KB', Helper::bytes(1024, 0));
        $this->assertEquals('1.0 KB', Helper::bytes(1024, 1));
    }

    public function test_capitalize()
    {
        $this->assertEquals('Abc', Helper::capitalize('abc'));
        $this->assertEquals('AbC', Helper::capitalize('AbC'));
        $this->assertEquals('A1C', Helper::capitalize('a1C'));
    }

    public function test_cycle()
    {
        $elements = [1, 2, 3];
        $cycler = new Helper\Cycler($elements);
        $this->assertTrue($cycler instanceof \IteratorAggregate);
        $this->assertEquals(1, $cycler->next());
        $this->assertEquals(2, $cycler->next());
        $this->assertEquals(3, $cycler->next());
        $this->assertTrue(in_array($cycler->random(), $elements));
    }

    public function test_date()
    {
        $time = time();
        $now = date('Y-m-d', $time);
        $this->assertEquals($now, Helper::date());
        $this->assertEquals($now, Helper::date($time));
    }

    public function test_dump()
    {
        $var = [1, 2, 3];
        $this->assertEquals(var_export($var, true), Helper::dump($var));
    }

    public function test_escape()
    {
        $var = '<p data-info="foo&bar">foobar</p>';
        $this->assertEquals('&lt;p data-info=&quot;foo&amp;bar&quot;&gt;foobar&lt;/p&gt;', Helper::escape($var));
    }

    public function test_first()
    {
        $string = 'Hello, World!';
        $this->assertEquals('H', Helper::first($string));

        $array = [1, 2, 3];
        $this->assertEquals(1, Helper::first($array));

        $arrayIterator = new \ArrayIterator($array);
        $this->assertEquals(1, Helper::first($arrayIterator));

        $object = new \StdClass;
        $object->foo = 'foo';
        $object->bar = 'bar';
        $this->assertEquals('foo', Helper::first($object));

        $this->assertEquals(42, Helper::first([], 42));
    }

    public function test_format()
    {
        $this->assertEquals('Hello, World!', Helper::format('Hello, %s', 'World!'));
    }

    public function test_is_iterable()
    {
        $this->assertTrue(Helper::is_iterable([1,2,3]));
        $this->assertTrue(Helper::is_iterable(new \ArrayIterator([1,2,3])));

        $this->assertFalse(Helper::is_iterable("hello world"));
        $this->assertFalse(Helper::is_iterable(0));
        $this->assertFalse(Helper::is_iterable(100));
        $this->assertFalse(Helper::is_iterable(3.14));
        $this->assertFalse(Helper::is_iterable(null));
        $this->assertFalse(Helper::is_iterable(true));
        $this->assertFalse(Helper::is_iterable(false));
    }

    public function test_is_divisible_by()
    {
        $this->assertTrue(Helper::is_divisible_by(10, 1));
        $this->assertTrue(Helper::is_divisible_by(10, 2));
        $this->assertFalse(Helper::is_divisible_by(10, 3));
    }

    public function test_is_empty()
    {
        $this->assertTrue(Helper::is_empty(null));
        $this->assertTrue(Helper::is_empty([]));
        $this->assertTrue(Helper::is_empty(new \ArrayIterator));
        $this->assertFalse(Helper::is_empty(new \StdClass));
    }

    public function test_is_even()
    {
        $this->assertTrue(Helper::is_even(10));
        $this->assertFalse(Helper::is_even(11));
        $this->assertTrue(Helper::is_even('FooBar'));
        $this->assertFalse(Helper::is_even('Foo Bar'));
    }

    public function test_is_odd()
    {
        $this->assertFalse(Helper::is_odd(10));
        $this->assertTrue(Helper::is_odd(11));
        $this->assertFalse(Helper::is_odd('FooBar'));
        $this->assertTrue(Helper::is_odd('Foo Bar'));
    }

    public function test_join()
    {
        $this->assertEquals('foobar', Helper::join(['foo', 'bar']));
        $this->assertEquals('foobar', Helper::join(new \ArrayIterator(['foo', 'bar'])));
    }

    public function test_json_encode()
    {
        $var = ['foo', 'bar', 1, 2, 3, ['x' => 'y']];
        $this->assertEquals(json_encode($var), Helper::json_encode($var));
    }

    public function test_keys()
    {
        $hash = ['x' => 1, 'y' => 2];
        $this->assertEquals(['x', 'y'], Helper::keys($hash));
    }

    public function test_last()
    {
        $string = 'Hello, World!';
        $this->assertEquals('!', Helper::last($string));

        $array = [1, 2, 3];
        $this->assertEquals(3, Helper::last($array));

        $arrayIterator = new \ArrayIterator($array);
        $this->assertEquals(3, Helper::last($arrayIterator));

        $object = new \StdClass;
        $object->foo = 'foo';
        $object->bar = 'bar';
        $this->assertEquals('bar', Helper::last($object));

        $this->assertEquals(42, Helper::last([], 42));
    }

    public function test_length()
    {
        $this->assertEquals(13, Helper::length('Hello, World!'));
        $this->assertEquals(3, Helper::length([1, 2, 3]));
        $this->assertEquals(1, Helper::length(1));
        $this->assertEquals(1, Helper::length(new \StdClass));
    }

    public function test_lower()
    {
        $this->assertEquals('foobar', Helper::lower('FooBar'));
        $this->assertEquals('123', Helper::lower(123));
    }

    public function test_nl2br()
    {
        $this->assertEquals("new<br>\nline", Helper::nl2br("new\nline"));
        $this->assertEquals("new<br />\nline", Helper::nl2br("new\nline", true));
    }

    public function test_number_format()
    {
        $this->assertEquals('12,059', Helper::number_format(12059.34));
        $this->assertEquals('12,059.34', Helper::number_format(12059.34, 2));
        $this->assertEquals('12.059,34', Helper::number_format(12059.34, 2, ',', '.'));
    }

    public function test_range()
    {
        $this->assertTrue(Helper::range(1, 10) instanceof Helper\RangeIterator);
    }

    public function test_repeat()
    {
        $this->assertEquals('xx', Helper::repeat('x'));
        $this->assertEquals('xxx', Helper::repeat('x', 3));
        $this->assertEquals('x', Helper::repeat('x', 1));
        $this->assertEquals('', Helper::repeat('x', 0));
    }

    public function test_replace()
    {
        $this->assertEquals('barbaric', Helper::replace('foobaric', 'foo', 'bar'));
        $this->assertEquals('vaporic', Helper::replace('foobaric', '/foobar/', 'vapor', true));
    }

    public function test_strip_tags()
    {
        $this->assertEquals('this is bold', Helper::strip_tags('this <i>is</i> <b>bold</b>'));
        $this->assertEquals('this <i>is</i> bold', Helper::strip_tags('this <i>is</i> <b>bold</b>', '<i>'));
    }

    public function test_title()
    {
        $this->assertEquals('Foo-bar', Helper::title('foo-bar'));
        $this->assertEquals('This Is The Title', Helper::title('this is the title'));
    }

    public function test_trim()
    {
        $this->assertEquals('foobar', Helper::trim('foobar '));
        $this->assertEquals('foobar', Helper::trim(' foobar'));
        $this->assertEquals('foo bar', Helper::trim(' foo bar '));
    }

    public function test_truncate()
    {
        $this->assertEquals('this is a &hellip;', Helper::truncate('this is a long word', 10));
        $this->assertEquals('this is a', Helper::truncate('this is a long word', 12, true, ''));
        $this->assertEquals('this is a ', Helper::truncate('this is a long word', 10, false, ''));
        $this->assertEquals('this is a ...', Helper::truncate('this is a long word', 10, false, '...'));
    }

    public function test_unescape()
    {
        $var = '&lt;p data-info=&quot;foo&amp;bar&quot;&gt;foobar&lt;/p&gt;';
        $this->assertEquals('<p data-info="foo&bar">foobar</p>', Helper::unescape($var));
    }

    public function test_upper()
    {
        $this->assertEquals('FOOBAR', Helper::upper('FooBar'));
        $this->assertEquals('123', Helper::upper(123));
    }

    public function test_url_encode()
    {
        $this->assertEquals('foo+bar', Helper::url_encode('foo bar'));
        $this->assertEquals('%23this', Helper::url_encode('#this'));
        $this->assertEquals('2%3E1', Helper::url_encode('2>1'));
    }

    public function test_word_wrap()
    {
        $this->assertEquals("this is on a line\nof its own", Helper::word_wrap('this is on a line of its own', 17));
        $this->assertEquals("this is on a line<br>of its own", Helper::word_wrap('this is on a line of its own', 17, '<br>'));
    }

}

