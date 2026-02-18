# API Reference - Capell Hero

Browse the `./src` directory for full details. Key entry points:

- Service Provider
    - `src/Providers/HeroServiceProvider.php`
- Filament form component
    - `src/Filament/Components/Forms/Page/HeroEditor.php`
- Blade view component
    - `src/View/Components/Widget/Hero.php`
- Enums
    - `src/Enums/ContentSchemaEnum.php`
    - `src/Enums/WidgetSchemaEnum.php`
    - `src/Enums/WidgetComponentEnum.php`
- Schema extender
    - `src/Filament/Extenders/Page/HeroPageSchemaExtender.php`
- Actions
    - `src/Actions/AddHeroToLayoutAction.php`
    - `src/Actions/CreateHeroWidgetAction.php`
    - `src/Actions/CreateHeroContentTypeAction.php`
    - `src/Actions/HeroWidgetHasPrimaryHeadingAction.php`
- Commands
    - `src/Commands/InstallCommand.php`
    - `src/Commands/DemoCommand.php`

Quick links:

- Source directory: `./src`
- Database reference: `./docs/Database.md`
