<?php

$inputFile  = $argv[1];
$percentage = min(100, max(0, (int) $argv[2]));

print_r('Input File is ' . $inputFile);

if (!file_exists($inputFile)) {
    print_r('Input File not exists ' . $inputFile);
    throw new InvalidArgumentException('Invalid input file provided');
}

if (!$percentage) {
    throw new InvalidArgumentException('An integer checked percentage must be given as second parameter');
}

print_r('Input File exists ' . $inputFile);
print_r('Input File contents ' . file_get_contents($inputFile));
$xml             = new SimpleXMLElement(file_get_contents($inputFile));
$metrics         = $xml->xpath('//metrics');
$totalElements   = 0;
$checkedElements = 0;

foreach ($metrics as $metric) {
    $totalElements   += (int) $metric['elements'];
    $checkedElements += (int) $metric['coveredelements'];
}

$coverage = ($checkedElements / $totalElements) * 100;

if ($coverage >= $percentage) {
    print_r('Code coverage is ' . $coverage);
    print_r(' -> Pull Request OK');
    return;
}

print_r('Code coverage is ' . round($coverage, 2) . '%, which is below the accepted ' . $percentage . '%');
print_r(' -> Pull Request Rejected');

