<?php

declare(strict_types=1);

use Capell\Core\Events\SiteReplicated;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\ReplicateSiteNavigationsAction;
use Capell\Navigation\Listeners\ReplicateSiteNavigationsListener;
use Capell\Navigation\Providers\NavigationServiceProvider;

it('copies no navigations to a replicated site while Navigation is disabled, and copies once re-enabled', function (): void {
    $source = Site::factory()->create();
    $replica = Site::factory()->create();
    $event = new SiteReplicated($source, $replica, ['copy_navigations' => true]);
    $replication = ReplicateSiteNavigationsAction::mock();

    CapellCore::markPackageDisabled(NavigationServiceProvider::$packageName);
    $replication->shouldNotReceive('handle');
    (new ReplicateSiteNavigationsListener)->handle($event);

    CapellCore::markPackageInstalled(NavigationServiceProvider::$packageName);
    ReplicateSiteNavigationsAction::mock()->shouldReceive('handle')->once();
    (new ReplicateSiteNavigationsListener)->handle($event);
});
