<?php

declare(strict_types=1);

namespace Capell\Workspaces\Checks;

use Capell\Workspaces\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BrokenLinkCheck implements PublishCheck
{
    public function identifier(): string
    {
        return 'broken-links';
    }

    public function label(): string
    {
        return 'Broken Links';
    }

    public function run(Workspace $workspace): PublishCheckResult
    {
        if (! Schema::hasTable('page_urls') || ! Schema::hasColumn('pages', 'body')) {
            return new PublishCheckResult(
                identifier: $this->identifier(),
                label: $this->label(),
                severity: PublishCheckSeverity::Info,
            );
        }

        $pages = DB::table('pages')
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('body')
            ->select(['uuid', 'slug', 'body'])
            ->get();

        $messages = [];
        $entityRefs = [];

        foreach ($pages as $page) {
            $internalLinks = $this->extractInternalLinks((string) $page->body);

            foreach ($internalLinks as $href) {
                $exists = DB::table('page_urls')
                    ->where('url', $href)
                    ->where(function ($query) use ($workspace): void {
                        $query->where('workspace_id', 0)
                            ->orWhere('workspace_id', $workspace->id);
                    })
                    ->exists();

                if (! $exists) {
                    $pageIdentifier = $page->slug ?? $page->uuid;
                    $messages[] = "Page '{$pageIdentifier}' contains broken link: {$href}";
                    $entityRefs[] = ['model' => 'Page', 'uuid' => $page->uuid];
                }
            }
        }

        if ($messages === []) {
            return new PublishCheckResult(
                identifier: $this->identifier(),
                label: $this->label(),
                severity: PublishCheckSeverity::Info,
            );
        }

        return new PublishCheckResult(
            identifier: $this->identifier(),
            label: $this->label(),
            severity: PublishCheckSeverity::Error,
            messages: $messages,
            entityRefs: $entityRefs,
        );
    }

    /** @return list<string> */
    private function extractInternalLinks(string $html): array
    {
        if ($html === '') {
            return [];
        }

        preg_match_all('/\bhref\s*=\s*"(\/[^"]*)"/i', $html, $matches);
        $fromDouble = $matches[1] ?? [];

        preg_match_all("/\\bhref\\s*=\\s*'(\\/[^']*)'/i", $html, $matches);
        $fromSingle = $matches[1] ?? [];

        return array_values(array_unique(array_merge($fromDouble, $fromSingle)));
    }
}
