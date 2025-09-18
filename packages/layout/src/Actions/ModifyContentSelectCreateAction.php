<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Filament\Resources\Contents\Schemas\ContentForm;
use Capell\Layout\Models\Content;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Select run(Select $select)
 */
class ModifyContentSelectCreateAction
{
    use AsObject;

    public function handle(Select $select): Select
    {
        return $select->createOptionForm(
            fn (mixed $state, Schema $schema): Schema => ContentForm::configure(
                $schema->operation('createOption')->model(Content::class)
            )
        )
            ->createOptionUsing(function (Select $component, array $data): int|string {
                $content = CreateContentAction::run($data);

                Notification::make()
                    ->title(__('capell-admin::message.content_created_successfully'))
                    ->body($content->name)
                    ->send();

                return $content->getKey();
            })
            ->createOptionAction(
                fn (Action $action): Action => $action
                    ->modal()
                    ->modalHeading(__('capell-admin::generic.type'))
                    ->fillForm(function (): array {
                        $site = Site::default()->first();

                        /** @var class-string<Type> $model */
                        $model = CapellCore::getModel(ModelEnum::Type);

                        return [
                            'type_id' => $model::query()
                                ->where('type', LayoutTypeEnum::Content)
                                ->default()
                                ->value('id'),
                            'translations' => $site->translations->mapWithKeys(fn ($translation): array => [
                                (string) Str::uuid() => [
                                    'language_id' => $translation->language_id,
                                ],
                            ])
                                ->all(),
                        ];
                    })
                    ->modalWidth(Width::ScreenLarge)
                    ->visible(fn (mixed $state, $record): bool => ! $state)
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.created_successfully',
                            ['name' => $action->getModalHeading()]
                        )
                    )
                    ->after(function (Action $action): void {
                        $action->success();
                    })
            );
    }
}
