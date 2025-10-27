<?php

declare(strict_types=1);

use Capell\Blog\Livewire\Page\ArchivePage;
use Capell\Blog\Livewire\Page\BlogPage;
use Capell\Blog\Livewire\Page\TagPage;

return [
    'livewire_components' => [
        'capell-blog::livewire.page.blog' => BlogPage::class,
        'capell-blog::livewire.page.archive' => ArchivePage::class,
        'capell-blog::livewire.page.tag' => TagPage::class,
    ],
];
