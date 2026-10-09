// Runs a periodic application health report for benchmark and deployment diagnostics.
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HealthCheck extends Command
{
    protected $signature = 'bench:health';

    protected $description = 'Write a compact process, memory, and database health log.';

    public function handle(): void
    {
        $databaseSize = DB::selectOne('SELECT SUM(data_length + index_length) AS bytes FROM information_schema.tables WHERE table_schema = DATABASE()')?->bytes ?? 0;
        $entry = [
            'timestamp' => now()->toIso8601String(),
            'memory_usage_bytes' => memory_get_usage(true),
            'memory_peak_usage_bytes' => memory_get_peak_usage(true),
            'database_size_bytes' => (int) $databaseSize,
        ];
        $handle = fopen(storage_path('logs/health.log'), 'ab');
        fwrite($handle, json_encode($entry).PHP_EOL);
        fclose($handle);
        $this->info('Health report written to storage/logs/health.log.');
    }
}
