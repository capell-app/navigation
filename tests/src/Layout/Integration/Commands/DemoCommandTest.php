<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\DemoCreator;
use Illuminate\Support\Facades\File;

it('runs the demo command and creates demo layouts for a site', function (): void {
    File::spy();
    $demoImgPath = DemoCreator::getDemoResourcePath('img');
    $dummyFile = new SplFileInfo('home.jpg', $demoImgPath, 'home.jpg');
    File::shouldReceive('files')->with($demoImgPath)->andReturn([$dummyFile]);
    File::shouldReceive('exists')->with(Mockery::on(fn (string $path): bool => str_starts_with($path, $demoImgPath)))->andReturn(false);

    $language = Language::factory()->english()->create();
    $site = Site::factory()->recycle($language)->withTranslations($language)->state(['name' => 'Test'])->create();
    Page::factory()->site($site)->home()->withTranslations()->create();

    Page::factory()->count(4)->site($site)->has(Media::factory())->create();

    // Act: run the command
    $this->artisan('capell:layout-demo', [
        '--sites' => $site->name,
    ])
        ->assertExitCode(0);

});
