<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TestReport extends Command
{
    protected $signature = 'test:report';

    protected $description = 'Run the test suite and write a JUnit summary CSV.';

    public function handle(): int
    {
        $process = new Process([
            PHP_BINARY,
            'artisan',
            'test',
            '--log-junit',
            'storage/logs/junit.xml',
        ], base_path());
        $process->run();

        $this->output->write($process->getOutput());
        $this->output->write($process->getErrorOutput());

        $xmlPath = storage_path('logs/junit.xml');
        if (! is_file($xmlPath)) {
            $this->error('JUnit XML was not created; no test report was written.');

            return $process->getExitCode() ?: 1;
        }

        $xml = @simplexml_load_file($xmlPath);
        if ($xml === false) {
            $this->error('Unable to parse JUnit XML; no test report was written.');

            return 1;
        }

        $outputPath = storage_path('reports/tests_'.now()->format('Ymd_His').'.csv');
        if (! is_dir(dirname($outputPath)) && ! mkdir(dirname($outputPath), 0777, true) && ! is_dir(dirname($outputPath))) {
            $this->error('Unable to create the storage/reports directory.');

            return 1;
        }

        $handle = fopen($outputPath, 'wb');
        if ($handle === false) {
            $this->error('Unable to create the test report CSV.');

            return 1;
        }

        fputcsv($handle, ['suite', 'tests', 'assertions', 'failures', 'errors', 'time_s']);
        foreach ($xml->xpath('//testsuite') as $suite) {
            fputcsv($handle, [
                (string) $suite['name'],
                (int) $suite['tests'],
                (int) $suite['assertions'],
                (int) $suite['failures'],
                (int) $suite['errors'],
                number_format((float) $suite['time'], 6),
            ]);
        }
        fclose($handle);

        $this->info("Wrote {$outputPath}");

        return $process->getExitCode() ?? 1;
    }
}