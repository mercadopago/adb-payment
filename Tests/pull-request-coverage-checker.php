<?php

use SimpleXMLElement;
use InvalidArgumentException;
use Exception;

function get_elements($cloverFile, $pullRequestFiles) {
    $xml             = new SimpleXMLElement(file_get_contents($cloverFile));
    $classes         = $xml->xpath('//class');
    $totalElements   = 0;
    $checkedElements = 0;

    foreach ($classes as $class) {
        if (in_array($class['name'], $pullRequestFiles)) {
            $totalElements   += (int) $class->metrics['elements'];
            $checkedElements += (int) $class->metrics['coveredelements'];
        }
    }

    return [
        'totalElements'   => $totalElements,
        'checkedElements' => $checkedElements,
    ];
}

/**
 * @SuppressWarnings(PHPMD.DevelopmentCodeFragment)
 */
function parse_pull_request_files($argv) {
    $pullRequestFiles = [];

    for ($i = 4; $i < count($argv); $i++) {
        $filename = str_replace('src/', '', $argv[$i]);
        $filename = str_replace('/', '\\', $filename);
        $filename = str_replace('.php', '', $filename);

        if (is_testable($filename)) {
            $pullRequestFiles[] = $filename;
            print_r($filename . ' is a testable file' . PHP_EOL);
        }
    }

    return $pullRequestFiles;
}

function is_testable($filename) {
    // Add all untestable php files
    $whitelist = [
        'Tests\coverage-checker',
        'Tests\pull-request-coverage-checker',
    ];

    return in_array($filename, $whitelist) ? false : true;
}

function is_hotfix_branch($branchName) {
    return strpos($branchName, 'hotfix');
}

function is_release_branch($branchName) {
    return strpos($branchName, 'release');
}

function is_skipped_branch($branchName) {
    return false === (is_hotfix_branch($branchName) || is_release_branch($branchName));
}

function validate_clover_file($cloverFile) {
    if (!file_exists($cloverFile)) {
        throw new InvalidArgumentException('Invalid clover file provided');
    }
}

function validate_percentage_param($percentage) {
    if (!$percentage) {
        throw new InvalidArgumentException('An integer checked percentage must be given as second parameter');
    }
}

/**
 * @SuppressWarnings(PHPMD.DevelopmentCodeFragment)
 */
function validate_pull_request_coverage($totalElements, $checkedElements, $percentage) {
    $coverage = ($checkedElements / $totalElements) * 100;

    if ($coverage >= $percentage) {
        print_r('Code coverage is ' . $coverage);
        print_r(' -> Pull Request OK');
        return;
    }

    print_r('Code coverage is ' . round($coverage, 2) . '%, which is below the accepted ' . $percentage . '%');
    print_r(' -> Pull Request Rejected');

    throw new Exception('Code coverage is ' . round($coverage, 2) . '%, which is below the accepted ' . $percentage . '%');
}

/**
 * @SuppressWarnings(PHPMD.DevelopmentCodeFragment)
 */
function execute($argv) {
    $branchName       = $argv[3];
    $cloverFile       = $argv[1];
    $percentage       = min(100, max(0, (int) $argv[2]));
    $pullRequestFiles = parse_pull_request_files($argv);

    if (false === is_skipped_branch($branchName)) {
        validate_clover_file($cloverFile);
        validate_percentage_param($percentage);

        $elements        = get_elements($cloverFile, $pullRequestFiles);
        $totalElements   = $elements['totalElements'];
        $checkedElements = $elements['checkedElements'];

        if ($totalElements == 0 || $checkedElements == 0) {
            if (count($pullRequestFiles) === 0) {
                print_r('Pull request does not contain testable files');
                return;
            }

            throw new Exception('Pull Request does not contain tested php files to check code coverage');
        }

        validate_pull_request_coverage($totalElements, $checkedElements, $percentage);

        return;
    }
}

execute($argv);
