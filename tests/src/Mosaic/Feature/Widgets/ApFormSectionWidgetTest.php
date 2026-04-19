<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Mosaic\Database\Factories\LayoutFactory;
use Capell\Mosaic\Enums\WidgetComponentEnum;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Pest\Expectation;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates ap form section widget with expected meta', function (): void {
    $widget = resolve(WidgetCreator::class)->apFormSectionWidget();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('ap-form-section')
        ->meta->scoped(
            fn (Expectation $expectation) => $expectation->component->toBe(WidgetComponentEnum::ApFormSection->value),
        );
});

it('renders ap form section widget title on page', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = resolve(WidgetCreator::class)->apFormSectionWidget();
    $translation = Translation::factory()->translatable($widget)->language($site->language)->create();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-ap-form-section',
            fn (AssertElement $element): BaseAssert => $element->containsText($translation->title),
        );
});

it('renders ap form section widget with submit button', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = resolve(WidgetCreator::class)->apFormSectionWidget();
    Translation::factory()->translatable($widget)->language($site->language)->create();

    $widget->update(['meta' => array_merge($widget->meta, [
        'submit_button_text' => 'Send Message',
        'form_fields' => [
            ['field_name' => 'name', 'field_label' => 'Full Name', 'field_type' => 'text', 'required' => true],
            ['field_name' => 'email', 'field_label' => 'Email Address', 'field_type' => 'email', 'required' => true],
        ],
    ])]);

    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-ap-form-section',
            fn (AssertElement $element): BaseAssert => $element
                ->containsText('Full Name')
                ->containsText('Email Address')
                ->containsText('Send Message'),
        );
});
