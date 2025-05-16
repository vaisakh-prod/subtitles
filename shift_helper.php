<?php

require_once __DIR__ . '/vendor/autoload.php';

use Done\Subtitles\Subtitles;

$options = getopt("", ["input:", "shift:", "output:"]);
if (!isset($options['input'], $options['shift'])) {
    echo "Usage: php tcin_shift_simple.php --input=example.srt --shift=2.5\n";
    exit(1);
}

$inputFile = $options['input'];
$shiftSeconds = floatval($options['shift']);
$outputFormat = $options['output'];

if (!file_exists($inputFile)) {
    echo "File does not exist: $inputFile\n";
    exit(1);
}

$pathInfo = pathinfo($inputFile);

$filenameWithExtension = $pathInfo['basename'];     
$filenameWithoutExtension = $pathInfo['filename'];

$outputDirectory = $pathInfo['dirname'];         

$outputFilename = $filenameWithoutExtension . "." . $outputFormat;
$outputPath = $outputDirectory . DIRECTORY_SEPARATOR . $outputFilename;

$subtitles = new Subtitles();
$lines = $subtitles->loadFromFile($inputFile);

$lines->shiftTime($shiftSeconds);
$lines->save($outputPath);


if (file_exists($inputFile)) {
    echo "Shifted subtitles saved to: $outputPath\n";
}
