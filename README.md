# Capell Packages

Optional add-on packages for [Capell CMS](https://github.com/capell-app/capell). Each package can be installed independently (subject to the dependency matrix below) via Composer.

## Packages

| Package   | Composer package         | Requires                  | Required by     | What it adds                                                              |
| --------- | ------------------------ | ------------------------- | --------------- | ------------------------------------------------------------------------- |
| Mosaic    | `capell-app/mosaic`      | `admin`, `frontend`       | `blog`, `address` | Visual layout builder, reusable widgets, content management              |
| Blog      | `capell-app/blog`        | `admin`, `frontend`, `mosaic` | —           | Article pages, tags, category archives                                    |
| Address   | `capell-app/address`     | `admin`, `frontend`, `mosaic` | —           | Country and address management on Sites                                   |
| Assistant | `capell-app/assistant`   | `admin`, `frontend`       | —               | OpenAI-powered title, meta, and content drafting                          |

> `admin` and `frontend` are core packages from [capell-app/capell](https://github.com/capell-app/capell) and are required by every add-on.

## Installing everything

```sh
composer require capell-app/mosaic capell-app/blog capell-app/address capell-app/assistant
php artisan capell:install
```

Add the VCS repositories to your `composer.json` first:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/capell-app/mosaic" },
        { "type": "vcs", "url": "https://github.com/capell-app/blog" },
        { "type": "vcs", "url": "https://github.com/capell-app/address" },
        { "type": "vcs", "url": "https://github.com/capell-app/assistant" }
    ]
}
```

## Documentation

Full documentation lives at [docs.capell.app](https://docs.capell.app). Per-package README and API/Database references are in each package directory under `packages/<name>/`.

## License

Proprietary — see each package for its license file.
