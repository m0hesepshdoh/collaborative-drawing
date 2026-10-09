<?php
// Converts PHPUnit JUnit XML into the test-count CSV used by the benchmark report.
$xmlPath = $argv[1] ?? 'storage/logs/junit.xml';
$outputPath = $argv[2] ?? 'benchmarks/tests_'.date('Ymd_His').'.csv';
$xml = simplexml_load_file($xmlPath);
if ($xml === false) {
    fwrite(STDERR, "Unable to parse JUnit XML.\n");
    exit(1);
}
$rows = [];
foreach ($xml->xpath('//testsuite') as $suite) {
    $rows[] = [
        'suite' => (string) $suite['name'],
        'tests' => (int) $suite['tests'],
        'assertions' => (int) $suite['assertions'],
        'failures' => (int) $suite['failures'],
        'errors' => (int) $suite['errors'],
        'time_s' => number_format((float) $suite['time'], 6),
    ];
}
mkdir(dirname($outputPath), 0777, true);
$handle = fopen($outputPath, 'wb');
fputcsv($handle, ['suite', 'tests', 'assertions', 'failures', 'errors', 'time_s']);
foreach ($rows as $row) fputcsv($handle, $row);
fclose($handle);
$tests = array_sum(array_column($rows, 'tests'));
$assertions = array_sum(array_column($rows, 'assertions'));
$failures = array_sum(array_column($rows, 'failures'));
$errors = array_sum(array_column($rows, 'errors'));
$time = array_sum(array_column($rows, 'time_s'));
echo sprintf("Tests: %d | Assertions: %d | Failures: %d | Errors: %d | Time: %.3f s\n", $tests, $assertions, $failures, $errors, $time);
