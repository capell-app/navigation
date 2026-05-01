@php
    use Capell\SeoTools\Data\SeoIssueData;
    use Capell\SeoTools\Enums\SeoIssueSeverityEnum;

    $criticalIssues = $hasReport ? collect($report->issues)->filter(fn (SeoIssueData $issue): bool => $issue->severity === SeoIssueSeverityEnum::Critical) : collect();
    $warningIssues = $hasReport ? collect($report->issues)->filter(fn (SeoIssueData $issue): bool => $issue->severity === SeoIssueSeverityEnum::Warning) : collect();
    $noticeIssues = $hasReport ? collect($report->issues)->filter(fn (SeoIssueData $issue): bool => $issue->severity === SeoIssueSeverityEnum::Notice) : collect();
    $passedChecks = $hasReport ? collect($report->passedChecks) : collect();
@endphp

<div
    class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"
>
    @if (! $hasReport)
        <div class="text-sm text-gray-600 dark:text-gray-300">
            {{ __('capell-seo-tools::generic.seo_panel_empty_state') }}
        </div>
    @else
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-sm font-medium text-gray-950 dark:text-white">
                    {{ __('capell-admin::navigation.seo_audit') }}
                </div>
                <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('capell-seo-tools::generic.seo_panel_passed_checks', ['count' => $passedChecks->count()]) }}
                </div>
            </div>

            <div
                class="rounded-md bg-gray-50 px-3 py-2 text-right dark:bg-gray-800"
            >
                <div
                    class="text-xs font-medium text-gray-500 dark:text-gray-400"
                >
                    {{ __('capell-seo-tools::generic.seo_panel_score') }}
                </div>
                <div
                    class="text-2xl font-semibold text-gray-950 dark:text-white"
                >
                    {{ $report->score }}
                </div>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-2">
            <div
                class="rounded-md border border-gray-200 p-3 dark:border-gray-700"
            >
                <div
                    class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400"
                >
                    {{ __('capell-seo-tools::generic.seo_panel_search_preview') }}
                </div>
                <div
                    class="text-primary-600 dark:text-primary-400 mt-2 text-base font-medium"
                >
                    {{ $report->searchPreview->title }}
                </div>
                <div class="mt-1 text-xs text-green-700 dark:text-green-400">
                    {{ $report->searchPreview->url }}
                </div>
                <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                    {{ $report->searchPreview->description }}
                </div>
            </div>

            <div
                class="rounded-md border border-gray-200 p-3 dark:border-gray-700"
            >
                <div
                    class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400"
                >
                    {{ __('capell-seo-tools::generic.seo_panel_social_preview') }}
                </div>
                <div
                    class="mt-2 text-base font-medium text-gray-950 dark:text-white"
                >
                    {{ $report->socialPreview->title }}
                </div>
                <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                    {{ $report->socialPreview->description }}
                </div>
                @if ($report->socialPreview->imageUrl !== null)
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ $report->socialPreview->imageUrl }}
                    </div>
                @endif
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            @foreach ([
                          SeoIssueSeverityEnum::Critical->getLabel() => $criticalIssues,
                          SeoIssueSeverityEnum::Warning->getLabel() => $warningIssues,
                          SeoIssueSeverityEnum::Notice->getLabel() => $noticeIssues,
                      ] as $severityLabel => $issues)
                <div
                    class="rounded-md border border-gray-200 p-3 dark:border-gray-700"
                >
                    <div
                        class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400"
                    >
                        {{ $severityLabel }} ({{ $issues->count() }})
                    </div>

                    @if ($issues->isEmpty())
                        <div
                            class="mt-2 text-sm text-gray-600 dark:text-gray-300"
                        >
                            {{ __('capell-seo-tools::generic.seo_panel_no_issues') }}
                        </div>
                    @else
                        <ul
                            class="mt-2 space-y-2 text-sm text-gray-700 dark:text-gray-300"
                        >
                            @foreach ($issues as $issue)
                                <li>{{ $issue->message }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
