# Database Reference - Capell Hero

The Hero package does not introduce its own database tables. It stores and renders data using the Layout package’s entities:

- Contents (`capell-app/layout` Content model)
- Widgets (`capell-app/layout` Widget model)
- Widget assets (`capell-app/layout` WidgetAsset model)

Install the Layout package first and run its migrations:

```
php artisan capell:layout-install
```

Then install Hero:

```
php artisan capell-hero:install
```

No additional migrations are required for Hero.
