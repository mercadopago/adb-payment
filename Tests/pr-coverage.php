<?php

$coverage  = $argv[1] ?? null;
$editedFilesList = $argv[2] ?? null;
$newFilesList = $argv[3] ?? null;
$percentage = min(100, max(0, (int) ($argv[4] ?? 100)));

if ($coverage === null || $editedFilesList === null || $newFilesList === null || $percentage === null) {
    echo "\n" . 'pr-coverage.php [clover.xml] [edited-fileslist.txt] [new-fileslist.txt] [percentage-coverage]' . "\n";
    die(1);
}

if (!file_exists($coverage)) {
    throw new InvalidArgumentException('Invalid clover file path');
}

if (!file_exists($editedFilesList)) {
    throw new InvalidArgumentException('Invalid edited files list file path');
}

if (!file_exists($newFilesList)) {
    throw new InvalidArgumentException('Invalid new files list file path');
}

function getPrFiles($file) {
    $prFiles = explode(' ', file_get_contents($file));
    foreach ($prFiles as $k => $file) {
        if (!str_contains($file, '.php') || str_contains($file, 'Tests/')) {
            unset($prFiles[$k]);
        }
    }

    return $prFiles;
}

$prEditedFiles = getPrFiles($editedFilesList);
$prNewFiles = getPrFiles($newFilesList);

$xml             = new SimpleXMLElement(file_get_contents($coverage));
$files           = $xml->xpath('//file');
$metrics         = [];
$totalElements   = 0;
$checkedElements = 0;

$validateFiles = [];

foreach ($files as $key => $file) {
    $fileName = (string) $file['name'];
    $metric = $file->metrics;
    $validateFiles[] = [
        'name' => $fileName,
        'elements' => (int) $metric['elements'],
        'coveredElements' => (int) $metric['coveredelements']
    ];
}

foreach ($validateFiles as $file) {
    foreach ($prEditedFiles as $prFile) {
        if (str_contains($file['name'], trim($prFile)) && $file['coveredElements'] > 0) {
            $checkedElements += $file['coveredElements'];
            $totalElements += $file['elements'];
        }
    }

    foreach ($prNewFiles as $prFile) {
        if (str_contains($file['name'], trim($prFile))) {
            $checkedElements += $file['coveredElements'];
            $totalElements += $file['elements'];
        }
    }
}

$coverage = $totalElements === 0 ? 100 : ($checkedElements / $totalElements) * 100;

echo "\nPull Request Code coverage is " . round($coverage) . "%.\n";
echo "Required coverage id " . round($percentage) . "%\n";

if ($coverage >= $percentage) {
    echo "Well Done! :)\n";
    exit;
}

echo "Pull Request Rejected! :(";
exit(1);
