# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Roadiz CMS **Legacy v1.x** — a PHP CMS built on Symfony 4.4/5.x components + Doctrine ORM. Active development moved to the v2.x monorepo (`roadiz/core-bundle-dev-app`). PHP `^7.4 || ^8.0`.

This is the *sources/development* repo, not a project skeleton — real sites are built from `roadiz/standard-edition`.

## Commands

Run PHP/composer inside Docker (`docker compose exec app …`) — see global docker preference. `bin/` is the composer bin-dir, so tools live at `bin/phpstan`, `bin/phpcs`, `bin/phpunit`, not `vendor/bin/`.

- **CLI entry point**: `bin/roadiz <command> [-e prod|dev] [--preview]` — the Symfony Console app (`RoadizApplication`). List commands with `bin/roadiz list`.
- **Lint + static analysis** (`make test` / `composer test`): runs `bin/phpcs` (PSR-2, config in `phpcs.xml.dist`), `bin/phpstan analyse -c phpstan.neon -l 4 src` (level 3 for themes), and `bin/roadiz lint:twig` on several view dirs.
- **Unit tests** (`make unit`): `bin/phpunit -v --bootstrap=tests/bootstrap.php --whitelist ./src tests/`. Run a single test: `bin/phpunit --bootstrap=tests/bootstrap.php tests/Core/SomeTest.php` or `--filter testName`. Tests need a `_test` database (see `db_test` in `compose.yml`).
- **Clear caches** (`make cache`): `bin/roadiz cache:clear` (repeat per env: default / `-e prod` / `-e prod --preview`, plus `cache:clear-fpm`). Cache clearing is also needed after config/node-type/route changes.
- **Migrate node-types** (`make migrate`): `bin/roadiz themes:migrate /Themes/<Theme>/<Theme>App` then clear caches — applies a theme's declared node-types to the DB schema.
- **Doctrine schema/migrations**: `bin/roadiz generate:nsentities` (regenerate node-source entities), `bin/roadiz orm:schema-tool:update` / `bin/doctrine-migrations`.
- **PHP dev server** (`make dev-server`): `php -S 0.0.0.0:8080 -t ./ conf/router.php`.

## Architecture

### Kernel & DI container (Pimple, not Symfony DI)
`src/Roadiz/Core/Kernel.php` is a custom HTTP kernel that is itself a Pimple `ServiceProviderInterface`. On boot it registers ~40 **ServiceProviders** (`src/Roadiz/Core/Services/*ServiceProvider.php` — Doctrine, Security, Routing, Solr, Twig, Serialization, etc.). The container is Pimple `$container`, accessed via `ContainerAwareInterface` / `ContainerAwareTrait` (`$this->get('service')`), *not* Symfony's autowired container. When adding a service, register it in a provider. `DevKernel`, `SourceKernel`, `PreviewServiceProvider` are environment variants.

### Polymorphic content schema (the core idea)
Content structure is data, not code. Editors define **NodeType**s and **NodeTypeField**s at runtime. From those definitions Roadiz *generates* PHP entity classes:
- `Node` = tree structure/position; `NodesSources` = the translated content payload for a node in one `Translation`.
- Concrete per-type classes are generated into `gen-src/GeneratedNodeSources/NS<Type>.php` (namespace `GeneratedNodeSources\`) via the entity generator. **Do not hand-edit `gen-src/`** — regenerate with `generate:nsentities` after node-type changes; `themes:migrate` keeps DB + generated entities in sync.
- Core entities live in `src/Roadiz/Core/Entities/` (many come from the `roadiz/models` package). Repositories in `src/Roadiz/Core/Repositories/` (base `EntityRepository`).

Related content axes: `Tag`/`Folder` (+ `*Translation`), `Attribute*` (typed metadata), `Document` (media, via `roadiz/documents`), `NodesToNodes` (node references), `UrlAlias`/`Redirection` (routing), `CustomForm*`.

### Themes (front-end + back-office)
`themes/` are self-contained apps under `Themes\` namespace. Each has a `<Name>App.php` extending the CMS controller stack, its own `Controllers/`, Twig `Resources/views/`, `config.yml`, node-type definitions, event subscribers and Console `Commands/`. `Rozier` is the back-office admin theme (dev-dep `roadiz/rozier`); `Install` is the installer; `DefaultTheme`/`FooTheme`/`Debug` are samples. `AppController` (`src/Roadiz/CMS/Controllers/`) is the abstract base for theme controllers.

### Routing
Two layers: static Symfony routes + a dynamic node router (symfony-cmf routing). Node URLs resolve through compiled matchers/generators in `gen-src/Compiled/` (`GlobalUrlMatcher`/`GlobalUrlGenerator`, PSR-0). Regenerate on route changes; clear route cache.

### Request lifecycle & events
Symfony HttpKernel event flow with many subscribers in `src/Roadiz/Core/Events/` (e.g. `ExceptionSubscriber`). Handlers wrapping entity operations live in `src/Roadiz/Core/Handlers/`. Async work uses Symfony Messenger (`src/Roadiz/Message/`, doctrine transport). Full-text search is Solr via Solarium (`src/Roadiz/Core/SearchEngine/`).

## Conventions

- Follow Conventional Commits (see global instructions); PHP files are PSR-2 with `declare(strict_types=1);`.
- Exception handling widens to `\Throwable` (PHP `Error`s reach `KernelEvents::EXCEPTION`) — don't narrow to `\Exception`.
- After changing config, node-types, routes, or Twig templates in prod, clear the relevant caches — stale compiled caches are a common source of "my change did nothing".
