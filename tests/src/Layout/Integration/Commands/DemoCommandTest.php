<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Creator\DemoCreator;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

it('runs the demo command and creates demo layouts for a site', function (): void {
    $demoImgPath = DemoCreator::getDemoResourcePath('img');
    $demoVideoPath = dirname($demoImgPath) . DIRECTORY_SEPARATOR . 'video';

    if (! is_dir($demoVideoPath)) {
        mkdir($demoVideoPath, 0777, true);
    }

    $demoVideoFile = $demoVideoPath . DIRECTORY_SEPARATOR . 'SampleVideo_1280x720_1mb.mp4';

    if (! file_exists($demoVideoFile)) {
        file_put_contents($demoVideoFile, '0');
    }

    File::spy();
    $dummyFile = new SplFileInfo($demoImgPath . DIRECTORY_SEPARATOR . 'home.jpg');
    File::shouldReceive('files')->with($demoImgPath)->andReturn([$dummyFile]);
    File::shouldReceive('exists')->with(Mockery::on(fn (string $path): bool => str_starts_with($path, $demoImgPath)))->andReturn(false);

    $language = Language::factory()->english()->create();
    $site = Site::factory()->recycle($language)->withTranslations($language)->state(['name' => 'Test'])->create();
    $type = Type::factory()->page()->default()->create();
    Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();

    Page::factory()->count(4)->site($site)->has(Media::factory())->create();

    artisan('capell:layout-demo', [
        '--sites' => $site->name,
    ])
        ->assertExitCode(0);
});
