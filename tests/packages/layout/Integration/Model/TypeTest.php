<?php

declare(strict_types=1);

use Capell\Core\Enums\TypeEnum as CoreTypeEnum;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\TypeEnum;
use Capell\Layout\Models;

it('has many contents', function (): void {
    $type = Type::factory()->content()->create();

    Models\Content::factory()->create(['type_id' => $type->id]);

    expect($type->refresh())
        ->contents->toHaveCount(1);
});

it('has many widgets', function (): void {
    $type = Type::factory()->widget()->create();

    Models\Widget::factory()->create(['type_id' => $type->id]);

    expect($type->refresh())
        ->widgets->toHaveCount(1);
});

it('can scope content type', function (): void {
    Type::factory()->create(['type' => TypeEnum::Content]);
    Type::factory()->create(['type' => CoreTypeEnum::Page]);

    $result = Type::contentType()->get();

    expect($result)->toHaveCount(1);
});

it('can scope widget type', function (): void {
    Type::factory()->create(['type' => TypeEnum::Widget]);
    Type::factory()->create(['type' => TypeEnum::Content]);

    $result = Type::widgetType()->get();

    expect($result)->toHaveCount(1);
});
