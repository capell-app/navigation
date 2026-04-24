<?php

declare(strict_types=1);

namespace Capell\Workspaces\Checks;

use Capell\Workspaces\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeoMetaCheck implements PublishCheck
{
    public function identifier(): string
    {
        return 'seo-meta';
    }

    public function label(): string
    {
        return 'SEO Meta';
    }

    public function run(Workspace $workspace): PublishCheckResult
    {
        if (! Schema::hasColumn('pages', 'meta_title') || ! Schema::hasColumn('pages', 'meta_description')) {
            return new PublishCheckResult(
                identifier: $this->identifier(),
                label: $this->label(),
                severity: PublishCheckSeverity::Info,
            );
        }

        $pages = DB::table('pages')
            ->where('workspace_id', $workspace->id)
            ->whereRaw("(meta_title IS NULL OR meta_title = '' OR meta_description IS NULL OR meta_description = '')")
            ->select(['id', 'slug'])
            ->get();

        if ($pages->isEmpty()) {
            return new PublishCheckResult(
                identifier: $this->identifier(),
                label: $this->label(),
                severity: PublishCheckSeverity::Info,
            );
        }

        $messages = $pages->map(function (object $page): string {
            $identifier = $page->slug ?? (string) $page->id;

            return "Page '{$identifier}' is missing meta title or meta description.";
        })->all();

        return new PublishCheckResult(
            identifier: $this->identifier(),
            label: $this->label(),
            severity: PublishCheckSeverity::Warn,
            messages: $messages,
        );
    }
}
