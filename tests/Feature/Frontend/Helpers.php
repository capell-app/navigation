<?php

declare(strict_types=1);

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Query log for test assertions.
 *
 * @var list<array{query: string, bindings: array<int, mixed>, time: float, trace: Collection<int, string>}>
 */
static $testQueryLog = [];

function setupQueryLogging(): void
{
    global $testQueryLog;
    $testQueryLog = [];

    DB::listen(function (QueryExecuted $query) use (&$testQueryLog): void {
        $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 100))
            ->map(fn (array $frame): ?string => isset($frame['file']) ? $frame['file'] . ':' . ($frame['line'] ?? '?') : null)
            ->filter();
        $testQueryLog[] = [
            'query' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
            'trace' => $trace,
        ];
    });
}

/**
 * @return list<array{query: string, bindings: array<int, mixed>, time: float, trace: Collection<int, string>}>
 */
function getTestQueryLog(): array
{
    global $testQueryLog;

    return $testQueryLog;
}

function clearTestQueryLog(): void
{
    global $testQueryLog;
    $testQueryLog = [];
}

/** @param array<array-key, mixed> $queryEntry */
function buildQuerySignature(array $queryEntry): string
{
    $query = $queryEntry['query'] ?? null;
    $bindings = $queryEntry['bindings'] ?? null;

    throw_if(! is_string($query) || ! is_array($bindings), RuntimeException::class, 'Expected query log entry to contain query text and bindings.');

    return $query . '|' . serialize($bindings);
}
