<?php

declare(strict_types=1);

namespace Capell\Blog\Console\Commands;

use Capell\Blog\Actions\CreateBlogPagesAction;
use Capell\Blog\Enums\ModelEnum as BlogModelEnum;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Console\Commands\Concerns\HasSitesOption;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Creator\DemoCreator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DemoCommand extends Command
{
    use HasSitesOption;

    protected $signature = 'capell:blog-demo {--sites=} {--user=} {--limit=}';

    protected $description = 'Setup demo blog pages, tags and sample articles for selected sites.';

    private BlogCreator $blogCreator;

    private DemoCreator $demoCreator;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $siteNames = $this->parseSitesOption();

        if (empty($siteNames)) {
            $this->error('No sites selected or provided.');

            return self::FAILURE;
        }

        $sites = $this->resolveSites($siteNames);

        if ($sites->isEmpty()) {
            $this->error('Unable to find any sites for: ' . implode(', ', $siteNames));

            return self::FAILURE;
        }

        $user = $this->resolveUser();
        $limit = $this->parseLimitOption();

        foreach ($sites as $index => $site) {
            if ($index > 0) {
                $this->newLine();
            }

            $this->runDemoForSite($site, $user, $limit);
        }

        $this->info('Blog demo setup completed for selected sites.');

        return self::SUCCESS;
    }

    /**
     * Parse the --sites option into an array of site names.
     *
     * @return array<int, string>
     */
    private function parseSitesOption(): array
    {
        $sitesOption = $this->option('sites');

        if ($sitesOption) {
            return is_string($sitesOption)
                ? array_filter(array_map('trim', explode(',', $sitesOption)))
                : (is_array($sitesOption) ? $sitesOption : []);
        }

        return $this->getDemoSites() ?? [];
    }

    /**
     * Resolve Site models for the given names.
     *
     * @param  array<int, string>  $siteNames
     * @return \Illuminate\Support\Collection<int, Site>
     */
    private function resolveSites(array $siteNames)
    {
        /** @var class-string<Site> $model */
        $model = CapellCore::getModel(\Capell\Core\Enums\ModelEnum::Site);

        return $model::query()
            ->with(['languages'])
            ->whereIn('name', $siteNames)
            ->get();
    }

    /**
     * Resolve the user for demo page authorship.
     */
    private function resolveUser(): ?Model
    {
        $userOption = $this->option('user');

        if ($userOption) {
            /** @var class-string<\Illuminate\Foundation\Auth\User> $model */
            $model = CapellCore::getModel('User');

            return $model::query()->find($userOption);
        }

        if (function_exists('auth') && auth()->check()) {
            $user = auth()->user();

            return $user instanceof Model ? $user : null;
        }

        return null;
    }

    /**
     * Parse and validate the --limit option.
     */
    private function parseLimitOption(): ?int
    {
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($limit !== null && (! is_int($limit) || $limit < 1)) {
            $this->warn('The --limit option must be a positive integer. No demo pages will be created.');

            return null;
        }

        return $limit;
    }

    /**
     * Run the demo setup for a single site.
     */
    private function runDemoForSite(
        Site $site,
        ?Model $user,
        ?int $limit,
    ): void {
        $this->info('Setting up demo blog for site: ' . $site->name);
        $this->newLine();

        $this->demoCreator = new DemoCreator(author: $user);

        $this->blogCreator = resolve(BlogCreator::class);

        $this->line('Ensuring required blog and ancillary pages exist...');
        CreateBlogPagesAction::run($site);
        $this->newLine();

        $this->line('Creating demo pages...');
        $created = $this->createDemoArticlesWithLimit($site, $user, $limit);

        if ($created) {
            $this->info('Demo pages created.');
        } else {
            $this->warn('Demo pages not created.');
        }

        $this->newLine();

        $this->line('Creating tags for site pages...');
        $this->createTags($site, $site->languages);
        $this->info('Tags created/updated.');
        $this->newLine();

        $this->line('Associating tags with pages...');
        $this->associatePageTags($site);
        $this->info('Tags associated with pages.');
        $this->newLine();
    }

    /**
     * Create demo pages for a site, respecting the global limit.
     */
    private function createDemoArticlesWithLimit(
        Site $site,
        ?Model $user,
        ?int $limit = null,
    ): bool {
        $site->loadMissing('languages', 'language');
        $blogPage = BlogLoader::getBlogPage($site);

        if (! $blogPage instanceof Page) {
            $this->warn('Blog page not found for site: ' . $site->name);

            return false;
        }

        $demo = config('capell-demo.pages');
        $createdCount = 0;

        $type = $this->blogCreator->createArticlePageType();

        foreach ($demo as $pageData) {
            if ($limit !== null && $createdCount >= $limit) {
                break;
            }

            $createdCount += $this->createDemoArticleRecursive(
                $pageData,
                $site,
                $site->languages,
                $site->language,
                $blogPage,
                '',
                $type,
                $user,
                $limit,
                $createdCount,
            );
        }

        $this->info(sprintf('%d demo articles created for site: %s', $createdCount, $site->name));

        return true;
    }

    /**
     * Recursively create demo pages, counting toward the global limit.
     * Returns the number of pages created in this branch.
     */
    private function createDemoArticleRecursive(
        array $data,
        Site $site,
        Collection $languages,
        Language $defaultLanguage,
        $parent,
        $parentName,
        Type $type,
        $author,
        ?int $limit,
        int $createdSoFar,
    ): int {
        if ($limit !== null && $createdSoFar >= $limit) {
            return 0;
        }

        $name = Str::title($data['name']['en']) . ' ' . $type->name;

        $full_name = in_array($parentName, [null, '', '0'], true)
            ? $name
            : sprintf('%s » %s', $parentName, $name);

        $this->line('Creating page: ' . $full_name);

        $variations = [
            'The Ultimate Guide to',
            'A Guide to Caring for',
            'Discovering the Secrets of',
            'Exploring the',
            'The Complete Guide to',
        ];

        $title = Arr::random($variations);

        foreach ($languages as $language) {
            $data['title'][$language->code] = $title . ' ' . $data['name'][$language->code];
        }

        $page = $this->demoCreator->createPage($data, $site, $languages, $parent, $type);

        $this->line(sprintf('Created page: %s (ID: ', $full_name) . ($page?->id ?? 'n/a') . ')');

        $created = 1;

        if (! isset($data['children']) || ($limit !== null && $createdSoFar + $created >= $limit)) {
            return $created;
        }

        $this->line('Recursing into children of: ' . $full_name);

        foreach ($data['children'] as $child) {
            if ($limit !== null && $createdSoFar + $created >= $limit) {
                break;
            }

            $created += $this->createDemoArticleRecursive(
                $child,
                $site,
                $languages,
                $defaultLanguage,
                $parent === false ? false : $page,
                $full_name,
                $type,
                $author,
                $limit,
                $createdSoFar + $created,
            );
        }

        return $created;
    }

    private function createTags(Site $site, $languages): void
    {
        /** @var class-string<Page> $model */
        $model = CapellCore::getModel(CoreModelEnum::Page);

        $pages = $model::query()
            ->where('site_id', $site->id)
            ->whereHas('children')
            ->whereRelation('type', 'key', 'article')
            ->with(['translations'])
            ->limit(50)
            ->get();
        $tagModel = CapellCore::getModel(BlogModelEnum::Tag);
        $pages->each(function (Page $page) use ($tagModel, $languages): void {
            $tag_names = [];
            $tag_slugs = [];
            $existingTag = null;
            $languages->each(function (Language $language) use (&$tag_names, &$tag_slugs, $page, $tagModel, &$existingTag): void {
                $translation = $page->translations->firstWhere('language_id', $language->id);
                if (! $translation) {
                    return;
                }

                $tag_names[$language->code] = Str::title($translation->label);
                $tag_slugs[$language->code] = Str::slug($translation->label);
                if ($existingTag === null) {
                    $existingTag = $tagModel::findFromString($translation->label, 'page', $language->code);
                }
            });
            if ($existingTag === null) {
                $tagModel::query()->create([
                    'type' => 'page',
                    'name' => $tag_names,
                    'slug' => $tag_slugs,
                ]);
            } else {
                $existingTag->update([
                    'name' => $tag_names,
                    'slug' => $tag_slugs,
                ]);
            }
        });
    }

    private function associatePageTags(Site $site): void
    {
        $tagModel = CapellCore::getModel(BlogModelEnum::Tag);
        $pageModel = CapellCore::getModel(CoreModelEnum::Page);
        $pages = $pageModel::query()
            ->with([
                'translations.language',
                'children',
            ])
            ->whereHas(
                'type',
                fn (BuilderContract $query): BuilderContract => $query->whereIn('key', ['default', 'article']),
            )
            ->notHomePage()
            ->where('site_id', $site->id)
            ->inRandomOrder()
            ->limit(50)
            ->get();
        foreach ($pages as $page) {
            $tag = false;
            foreach ($page->translations as $translation) {
                $tag = $tagModel::findFromString($translation->label, 'page', $translation->language->code);
                if ($tag) {
                    break;
                }
            }

            if ($tag) {
                $page->tags()->syncWithoutDetaching($tag);
                $page->children->take(10)->each(function (Page $childPage) use ($tag): void {
                    $childPage->tags()->syncWithoutDetaching($tag);
                });
            }
        }
    }
}
