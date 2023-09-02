<?php

namespace Tests\Formats;

use Done\Subtitles\Code\Converters\VttConverter;
use Done\Subtitles\Code\Formats\Vtt;
use Done\Subtitles\Code\Helpers;
use Done\Subtitles\Code\UserException;
use Done\Subtitles\Subtitles;
use PHPUnit\Framework\TestCase;
use Helpers\AdditionalAssertionsTrait;

class VttTest extends TestCase {

    use AdditionalAssertionsTrait;

    public function testRecognizesVtt()
    {
        $content = file_get_contents('./tests/files/vtt.vtt');
        $converter = Helpers::getConverterByFileContent($content);
        $this->assertTrue(get_class($converter) === VttConverter::class);
    }

    public function testDoesntTakeFilesMentioningVtt()
    {
        $content = 'something 
about WEBVTT';
        $converter = Helpers::getConverterByFileContent($content);
        $this->assertTrue(get_class($converter) !== VttConverter::class);
    }

    public function testConvertFromVttToSrt()
    {
        $vtt_path = './tests/files/vtt.vtt';
        $srt_path = './tests/files/srt.srt';

        $expected = (new Subtitles())->loadFromFile($vtt_path)->content('srt');
        $actual = file_get_contents($srt_path);

        $this->assertStringEqualsStringIgnoringLineEndings($expected, $actual);
    }

    public function testConvertFromSrtToVtt()
    {
        $srt_path = './tests/files/srt.srt';
        $vtt_path = './tests/files/vtt.vtt';

        $expected = file_get_contents($vtt_path);
        $actual = (new Subtitles())->loadFromFile($srt_path)->content('vtt');

        $this->assertStringEqualsStringIgnoringLineEndings($expected, $actual);
    }

    public function testFileToInternalFormat()
    {
        $vtt_path = './tests/files/vtt_with_name.vtt';
        $expected_internal_format = [[
            'start' => 9,
            'end' => 11,
            'lines' => ['We are in New York City'],
            'vtt' => [
                'speakers' => ['Roger Bingham']
            ]
        ]];

        $actual_internal_format = Subtitles::loadFromFile($vtt_path)->getInternalFormat();

        $this->assertInternalFormatsEqual($expected_internal_format, $actual_internal_format);
    }

    public function testParsesFileWithCue()
    {
        $input_vtt_file_content = <<< TEXT
WEBVTT

1
00:00:00.000 --> 00:00:01.000
a

some text allowed in vtt that is not shown
00:00:01.000 --> 00:00:02.000
b
b

something
00:00:02.000 --> 00:00:03.000
c

TEXT;

        $actual = Subtitles::loadFromString($input_vtt_file_content)->getInternalFormat();
        $expected = (new Subtitles())->add(0, 1, 'a')->add(1, 2, ['b', 'b'])->add(2, 3, 'c')->getInternalFormat();

        $this->assertInternalFormatsEqual($expected, $actual);
    }

    public function testExceptionIfWrongTimestamp()
    {
        $this->expectException(UserException::class);

        $input_vtt_file_content = <<< TEXT
WEBVTT

1
00:00:00.00 --> 00:00:01.o0
a
TEXT;

        Subtitles::loadFromString($input_vtt_file_content)->getInternalFormat();
    }

    public function testNoExceptionWhenEmptyFile()
    {
        $this->expectException(UserException::class);

        $input_vtt_file_content = <<< TEXT
WEBVTT

TEXT;

        Subtitles::loadFromString($input_vtt_file_content)->getInternalFormat();
    }


    public function testParsesFileWithMissingText()
    {
        $vtt_path = './tests/files/vtt_with_missing_text.vtt';
        $actual = (new Subtitles())->loadFromFile($vtt_path)->getInternalFormat();
        $expected = [
            [
                'start' => 0,
                'end' => 1,
                'lines' => [
                    'one',
                ],
            ], [
                'start' => 2,
                'end' => 3,
                'lines' => [
                    'three',
                ],
            ]
        ];
        $this->assertInternalFormatsEqual($expected, $actual);
    }

        public function testFileContainingMultipleNewLinesBetweenBlocks()
    {
        $given = <<< TEXT
WEBVTT

00:00:00.000 --> 00:00:01.000
text1





00:00:01.000 --> 00:00:02.000
text2
TEXT;
        $actual = (new Subtitles())->loadFromString($given)->getInternalFormat();

        $expected = (new Subtitles())
            ->add(0, 1, 'text1')
            ->add(1, 2, 'text2')
            ->getInternalFormat();

        $this->assertEquals($expected, $actual);
    }

    public function testParsesFileWithStyles()
    {
        $given = file_get_contents('./tests/files/vtt_with_styles.vtt');
        $actual = (new Subtitles())->loadFromString($given)->getInternalFormat();

        $expected = (new Vtt())
            ->add(0.0, 10.0, 'Hello world.', ['settings' => 'position:50% line:15% align:middle'])
            ->getInternalFormat();

        $this->assertEquals($expected, $actual);
    }

    public function testGeneratesFileWithStyles()
    {
        $actual = (new Vtt())->add(0, 1, 'a', ['settings' => 'position:50% line:15% align:middle'])->content('vtt');
        $expected = <<<X
WEBVTT

00:00:00.000 --> 00:00:01.000 position:50% line:15% align:middle
a
X;
        $this->assertStringEqualsStringIgnoringLineEndings($expected, $actual);
    }

    public function testGeneratesSpeakers()
    {
        $actual = (new Vtt())->add(0, 1, ['John' => 'a', 'b'])->content('vtt');
        $expected = <<<X
WEBVTT

00:00:00.000 --> 00:00:01.000
<v John>a</v>
b
X;
        $this->assertStringEqualsStringIgnoringLineEndings($expected, $actual);
    }

    public function testParsesSpeakers()
    {
        $expected = (new Vtt())->add(0, 1, ['John' => 'a', 'b'])->getInternalFormat();
        $input = <<<X
WEBVTT

00:00:00.000 --> 00:00:01.000
<v John>a</v>
b
X;
        $actual = Subtitles::loadFromString($input)->getInternalFormat();
        $this->assertEquals($expected, $actual);
    }

    public function testParsesFileWithHtml()
    {
        $given = file_get_contents('./tests/files/vtt_with_html.vtt');
        $actual = (new Subtitles())->loadFromString($given)->getInternalFormat();

        $expected = (new Subtitles())
            ->add(0.0, 10.0, 'Sur les playground, ici à Montpellier')
            ->getInternalFormat();

        $this->assertEquals($expected, $actual);
    }

    public function testParsesFileWithoutHoursInTimestamp()
    {
        $given = file_get_contents('./tests/files/vtt_without_hours_in_timestamp.vtt');
        $actual = (new Subtitles())->loadFromString($given)->getInternalFormat();

        $expected = (new Subtitles())
            ->add(0.0, 10.0, "I've spent nearly two decades")
            ->getInternalFormat();

        $this->assertEquals($expected, $actual);
    }

    public function testParsesFileWithMultipleNewLines()
    {
        $given = file_get_contents('./tests/files/vtt_with_multiple_new_lines.vtt');
        $actual = (new Subtitles())->loadFromString($given)->getInternalFormat();
        $expected = (new Subtitles())
            ->add(0.0, 1.0, ['one', 'two'])
            ->add(2.0, 3.0, 'three')
            ->getInternalFormat();

        $this->assertEquals($expected, $actual);
    }

    public function testParseFileWithMetadata()
    {
        $given = <<< TEXT
WEBVTT
X-TIMESTAMP-MAP=LOCAL:00:00:00.000,MPEGTS:0

00:00:00.000 --> 00:00:01.000
text1
TEXT;
        $actual = (new Subtitles())->loadFromString($given)->getInternalFormat();
        $expected = (new Subtitles())
            ->add(0, 1, 'text1')
            ->getInternalFormat();

        $this->assertEquals($expected, $actual);
    }

    public function testParseFileWithSpacesBetweenTimestamps()
    {
        $given = <<< TEXT
WEBVTT

     00:00:00.100    -->    00:00:01.100     
text1
TEXT;
        $actual = (new Subtitles())->loadFromString($given)->getInternalFormat();
        $expected = (new Subtitles())
            ->add(0.1, 1.1, 'text1')
            ->getInternalFormat();

        $this->assertEquals($expected, $actual);
    }

    public function testTimeFormats()
    {
        $given = <<< TEXT
WEBVTT

00:00:01.10 --> 00:00:02.50
one
TEXT;
        $actual = (new Subtitles())->loadFromString($given)->getInternalFormat();
        $expected = (new Subtitles())
            ->add(1.1, 2.5, 'one')
            ->getInternalFormat();
        $this->assertEquals($expected, $actual);
    }

    public function testTimeFormats2()
    {
        $given = <<< TEXT
WEBVTT

00:03.30 --> 00:04.40
two
TEXT;
        $actual = (new Subtitles())->loadFromString($given)->getInternalFormat();
        $expected = (new Subtitles())
            ->add(3.3, 4.4, 'two')
            ->getInternalFormat();
        $this->assertEquals($expected, $actual);
    }

    public function testTimeFormats3()
    {
        $given = <<< TEXT
WEBVTT

1:00:00.000 --> 1:00:01.000
three
TEXT;
        $actual = (new Subtitles())->loadFromString($given)->getInternalFormat();
        $expected = (new Subtitles())
            ->add(3600.0, 3601.0, 'three')
            ->getInternalFormat();
        $this->assertEquals($expected, $actual);
    }

    public function testParsesFileWithExtraNewLines()
    {
        $given = <<< TEXT
WEBVTT



00:00:01.10 --> 00:00:02.50

one


00:00:03.30 --> 00:00:04.40

two
TEXT;
        $actual = (new Subtitles())->loadFromString($given)->getInternalFormat();
        $expected = (new Subtitles())
            ->add(1.1, 2.5, 'one')
            ->add(3.3, 4.4, 'two')
            ->getInternalFormat();
        $this->assertEquals($expected, $actual);
    }

    public function testParsesIncorrectTimestampWithComma()
    {
        $given = <<< TEXT
WEBVTT

00:00:01,01 --> 00:00:02.02
a
TEXT;
        $actual = (new Subtitles())->loadFromString($given)->getInternalFormat();
        $expected = (new Subtitles())
            ->add(1.01, 2.02, 'a')
            ->getInternalFormat();
        $this->assertEquals($expected, $actual);
    }
}