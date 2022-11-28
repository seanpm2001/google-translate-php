<?php

namespace Stichoza\GoogleTranslate\Tests;

use Exception;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Stichoza\GoogleTranslate\GoogleTranslate;

class UtilityTest extends TestCase
{
    public GoogleTranslate $tr;

    public ReflectionMethod $method;

    public function setUp(): void
    {
        $this->tr = new GoogleTranslate();
        $reflection = new ReflectionClass(get_class($this->tr));
        $this->method = $reflection->getMethod('isValidLocale');
        $this->method->setAccessible(true);
    }

    public function testIsValidLocale()
    {
        $m = $this->method;
        $t = $this->tr;

        $booleanAssertions = [
            'ab'       => true, // ka, ge, ua
            'ab-CD'    => true, // zh-CN, zh-TW
            'ab-CDE'   => true, //
            'abc-DE'   => true,
            'abc-DEF'  => true,
            'abc-Defg' => true, // mni-Mtei
            'abc'      => true, // fil, gom, ckb
            'abcd'     => false,
            'ab-'      => false,
            'a'        => false,
        ];

        foreach ($booleanAssertions as $key => $value) {
            $this->assertEquals($m->invokeArgs($t, [$key]), $value);
        }
    }

    public function testSetOptions()
    {
        $res = fopen('php://memory', 'r+');

        $this->tr->setOptions([
            'debug'   => $res,
            'headers' => [
                'User-Agent' => 'Foo',
            ],
        ])->translate('hello');
        rewind($res);
        $output = str_replace("\r", '', stream_get_contents($res));
        $this->assertStringContainsString('User-Agent: Foo', $output);

        GoogleTranslate::trans('world', 'en', null, [
            'debug'   => $res,
            'headers' => [
                'User-Agent' => 'Bar',
            ],
        ]);
        rewind($res);
        $output = str_replace("\r", '', stream_get_contents($res));

        $this->assertStringContainsString('User-Agent: Bar', $output);

        fclose($res);
    }

    public function testSetUrl()
    {
        $res = fopen('php://memory', 'r+');

        try {
            $this->tr
                ->setUrl('https://translate.google.cn/translate_a/single')
                ->setOptions(['debug' => $res])
                ->translate('hello');
        } catch (Exception) {}

        rewind($res);
        $output = str_replace("\r", '', stream_get_contents($res));

        $this->assertStringContainsString('Host: translate.google.cn', $output);
        $this->assertStringContainsString('Connected to translate.google.cn', $output);

        fclose($res);
    }
}
