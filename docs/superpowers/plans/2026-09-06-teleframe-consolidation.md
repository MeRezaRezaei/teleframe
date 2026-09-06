# Teleframe Ecosystem Consolidation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Pivot from a split-package monorepo back into a single "batteries-included" Composer package named `teleframe`, retaining exact internal concern boundaries (Core, Bot, Laravel, Schema) via sub-namespaces.

**Architecture:** A single Composer package (`merezarezaei/teleframe`) exposing multiple PSR-4 paths mapping perfectly to physical directories (`src/Core`, `src/Bot`, `src/Laravel`, `src/Schema`). The `Teleproto` brand is entirely replaced with `Teleframe`.

**Tech Stack:** PHP 8.2+, Composer, Bash.

**Spec:** Current workspace state + User directive to revert the splits into an internally modular single-package framework named `teleframe`. 

## Global Constraints

- **Single Package Rule:** The repository produces exactly one published package: `merezarezaei/teleframe`.
- **Zero Regex in Core:** Core wire/parsing components strictly forbid `preg_*()` usage.
- **Strict Dependencies:** Only the `Laravel` layer may depend on `illuminate/*` framework components. The `Core` remains framework-agnostic.
- **Git State:** Ensure sequential commits for clear history pivoting.
- **Atomic Renames:** `Teleproto` -> `Teleframe`, `teleproto` -> `teleframe`.

---

### Task 1: Root Configuration & Directory Consolidation

Move the source files from the sub-packages into the root `src/` directory and merge all dependencies into a single, unified `composer.json` declaring `merezarezaei/teleframe`.

**Files:**
- Modify: `composer.json`
- Delete: `packages/` (after moves)

**Interfaces:**
- Produces: Base `teleframe` folder structure and PSR-4 resolution map.

- [ ] **Step 1: Write the unified composer.json**

```json
{
    "name": "merezarezaei/teleframe",
    "description": "Unified Telegram engine and framework (MTProto 2.0 & Bot API)",
    "type": "library",
    "license": "MIT",
    "authors": [
        {
            "name": "Teleframe Team"
        }
    ],
    "require": {
        "php": "^8.2",
        "ext-bcmath": "*",
        "ext-gmp": "*",
        "ext-openssl": "*",
        "ext-zlib": "*",
        "ext-json": "*",
        "ext-fileinfo": "*",
        "illuminate/contracts": "^10.0 || ^11.0 || ^12.0",
        "illuminate/support": "^10.0 || ^11.0 || ^12.0",
        "illuminate/console": "^10.0 || ^11.0 || ^12.0",
        "illuminate/http": "^10.0 || ^11.0 || ^12.0",
        "illuminate/events": "^10.0 || ^11.0 || ^12.0",
        "illuminate/routing": "^10.0 || ^11.0 || ^12.0",
        "symfony/console": "^6.0 || ^7.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "phpstan/phpstan": "^1.10",
        "orchestra/testbench": "^8.0 || ^9.0",
        "vlucas/phpdotenv": "^5.5"
    },
    "autoload": {
        "psr-4": {
            "MeRezaRezaei\\Teleframe\\Core\\": "src/Core/",
            "MeRezaRezaei\\Teleframe\\Bot\\": "src/Bot/",
            "MeRezaRezaei\\Teleframe\\Laravel\\": "src/Laravel/",
            "MeRezaRezaei\\Teleframe\\Schema\\": "src/Schema/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "MeRezaRezaei\\Teleframe\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "MeRezaRezaei\\Teleframe\\Laravel\\Providers\\TeleframeServiceProvider"
            ],
            "aliases": {
                "TF": "MeRezaRezaei\\Teleframe\\Laravel\\Facades\\TF",
                "Teleframe": "MeRezaRezaei\\Teleframe\\Laravel\\Facades\\Teleframe"
            }
        }
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    }
}
```

- [ ] **Step 2: Move package source files into the unified root**

```bash
rm composer.lock
mkdir -p src/Core src/Bot src/Laravel src/Schema
mv packages/core/src/* src/Core/
mv packages/bot/src/* src/Bot/
mv packages/laravel/src/* src/Laravel/
mv packages/schema/src/* src/Schema/
# Move schema and skills data configs
cp -r packages/schema/schema src/Schema/schema
cp -r packages/schema/skills src/Schema/skills
cp -r packages/schema/config src/Schema/config
```

- [ ] **Step 3: Test composer setup**

Run: `composer install`
Expected: Passes and generates autoload files mapped to `src/Core`, etc.

- [ ] **Step 4: Commit**

```bash
git add composer.json src/
git commit -m "build: consolidate into merezarezaei/teleframe root package"
```

---

### Task 2: Codebase Namespace Renaming (Brand switch)

Execute a bulk replacement across the entire `src/` directory to switch from `MeRezaRezaei\Teleproto` to the new target concern namespaces.

**Files:**
- Modify: `src/**/*.php`

**Interfaces:**
- Consumes: The combined `src/` directory from Task 1.
- Produces: Correctly namespaced codebase representing the Teleframe brand.

- [ ] **Step 1: Write and apply the bulk rename script**

```bash
cat << 'EOF' > rename.php
<?php
$dir = new RecursiveDirectoryIterator('src');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $content = file_get_contents($file->getPathname());
        
        // 1. Core namespace replacement
        $content = str_replace('namespace MeRezaRezaei\Teleproto;', 'namespace MeRezaRezaei\Teleframe\Core;', $content);
        $content = str_replace('namespace MeRezaRezaei\Teleproto\\', 'namespace MeRezaRezaei\Teleframe\Core\\', $content);
        $content = preg_replace('/use MeRezaRezaei\\\\Teleproto\\\\(?!Schema|Bot|Laravel)/', 'use MeRezaRezaei\Teleframe\Core\\', $content);

        // 2. Specialized packages namespace replacement
        $content = str_replace('MeRezaRezaei\TeleprotoBot', 'MeRezaRezaei\Teleframe\Bot', $content);
        $content = str_replace('MeRezaRezaei\TeleprotoLaravel', 'MeRezaRezaei\Teleframe\Laravel', $content);
        $content = str_replace('MeRezaRezaei\TeleprotoSchema', 'MeRezaRezaei\Teleframe\Schema', $content);
        
        // 3. Brand naming replacements
        $content = str_replace('TeleprotoClient', 'TeleframeClient', $content);
        $content = str_replace('TeleprotoAuthService', 'TeleframeAuthService', $content);
        $content = str_replace('teleproto:', 'teleframe:', $content);
        $content = str_replace('teleproto-config', 'teleframe-config', $content);
        
        file_put_contents($file->getPathname(), $content);
    }
}
echo "Renamed.\n";
EOF
php rename.php
rm rename.php
```

- [ ] **Step 2: Run verification**

Run: `composer dump-autoload`
Expected: No parsing errors (graceful completion). Tests will fail, which is expected before Task 3.

- [ ] **Step 3: Commit**

```bash
git add src/
git commit -m "refactor: bulk rename Teleproto to Teleframe namespaces"
```

---

### Task 3: Test Consolidation and Fixes

Migrate tests from the sub-packages into a single unified `tests/` directory under `tests/Core`, `tests/Bot`, `tests/Laravel`, `tests/Schema`. Provide a root `phpunit.xml.dist`.

**Files:**
- Create: `phpunit.xml.dist`
- Modify: `tests/**/*.php` (Namespace updates matching Task 2)

**Interfaces:**
- Consumes: The `tests/` payloads from previous packages.
- Produces: Unified green test suite.

- [ ] **Step 1: Scaffold test directory and move definitions**

```bash
mkdir -p tests/Core tests/Bot tests/Laravel tests/Schema
mv packages/core/tests/* tests/Core/
mv packages/bot/tests/* tests/Bot/
mv packages/laravel/tests/* tests/Laravel/
mv packages/schema/tests/* tests/Schema/
```

- [ ] **Step 2: Apply namespace updates to tests**

```bash
cat << 'EOF' > rename_tests.php
<?php
$dir = new RecursiveDirectoryIterator('tests');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $content = file_get_contents($file->getPathname());
        $content = str_replace('MeRezaRezaei\Teleproto', 'MeRezaRezaei\Teleframe\Core', $content);
        $content = str_replace('MeRezaRezaei\Teleframe\CoreBot', 'MeRezaRezaei\Teleframe\Bot', $content);
        $content = str_replace('MeRezaRezaei\Teleframe\CoreLaravel', 'MeRezaRezaei\Teleframe\Laravel', $content);
        $content = str_replace('MeRezaRezaei\Teleframe\CoreSchema', 'MeRezaRezaei\Teleframe\Schema', $content);
        $content = str_replace('TeleprotoClient', 'TeleframeClient', $content);
        file_put_contents($file->getPathname(), $content);
    }
}
echo "Tests Renamed.\n";
EOF
php rename_tests.php
rm rename_tests.php
```

- [ ] **Step 3: Write unified `phpunit.xml.dist`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Core">
            <directory>tests/Core</directory>
        </testsuite>
        <testsuite name="Bot">
            <directory>tests/Bot</directory>
        </testsuite>
        <testsuite name="Laravel">
            <directory>tests/Laravel</directory>
        </testsuite>
        <testsuite name="Schema">
            <directory>tests/Schema</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

- [ ] **Step 4: Test execution**

Run: `vendor/bin/phpunit`
Expected: 100% PASS (Address minor fully-qualified replacements inline if any fail).

- [ ] **Step 5: Commit**

```bash
git add tests/ phpunit.xml.dist
git commit -m "test: consolidate unified test suite for teleframe"
```

---

### Task 4: Generators and Path Re-alignment

Move binary scripts from `packages/schema/bin/*` to `bin/` at the root. Update paths inside `SchemaArtifacts` and generators.

**Files:**
- Modify: `src/Schema/SchemaArtifacts.php`
- Modify: `bin/generate-*.php`
- Modify: `phpstan.neon.dist`

**Interfaces:**
- Produces: Correct executable tools for the schema pipeline in a unified repo.

- [ ] **Step 1: Re-align binaries and configuration**

```bash
mkdir -p bin
mv packages/schema/bin/* bin/
rm -rf packages/ # Package folder is fully deprecated
```

- [ ] **Step 2: Update SchemaArtifacts.php manually**

Write minimal implementation fixing the artifact path inside `src/Schema/SchemaArtifacts.php`:
```php
<?php
namespace MeRezaRezaei\Teleframe\Schema;

class SchemaArtifacts
{
    public static function path(string $relative = ''): string
    {
        // Now resolves straight down from Schema src component
        $base = __DIR__;
        return $relative ? $base . '/' . ltrim($relative, '/') : $base;
    }
}
```

- [ ] **Step 3: Unified PHPStan**

Create root `phpstan.neon.dist`:
```neon
parameters:
    level: 5
    paths:
        - src
    disallowedFunctionCalls:
        -
            function: 'preg_*'
            message: 'Regex is banned in the core teleframe engine. Use tokenizer or string funcs.'
            allowIn:
                - src/Laravel/*
                - src/Schema/*
                - src/Bot/*
```

- [ ] **Step 4: Run static analysis**

Run: `composer require --dev spaze/phpstan-disallowed-calls`
Run: `vendor/bin/phpstan analyse -c phpstan.neon.dist`
Expected: [OK] No Errors.

- [ ] **Step 5: Commit**

```bash
git add bin/ src/Schema/ phpstan.neon.dist
git rm -r packages/
git commit -m "build: finalize paths and tooling for single unified framework"
```

---

### Task 5: Teleclient Integration Update

Switch the consumer `teleclient` in the neighboring directory (`../teleclient`) back to simple integration against the unified `merezarezaei/teleframe` package.

**Files:**
- Modify: `../teleclient/composer.json`
- Modify: `../teleclient/src/**/*.php` (Namespace updates to `Teleframe`)

- [ ] **Step 1: Set teleclient `composer.json` back to base repo**

```json
    "require": {
        "php": "^8.2",
        "merezarezaei/teleframe": "*"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../teleproto", 
            "options": {
                "symlink": true
            }
        }
    ]
```

- [ ] **Step 2: Mass rename teleclient imports**

```bash
cd ../teleclient
find src tests -type f -name "*.php" -exec sed -i '' 's/MeRezaRezaei\\Teleproto/MeRezaRezaei\\Teleframe\\Core/g' {} +
find src tests -type f -name "*.php" -exec sed -i '' 's/TeleprotoClient/TeleframeClient/g' {} +
```

- [ ] **Step 3: Install & Verify teleclient**

Run: `composer update` in `teleclient`.
Run: `vendor/bin/phpunit` in `teleclient`.
Expected: Tests pass, proving transparent capability.

- [ ] **Step 4: Commit teleclient**

```bash
git add composer.json src/ tests/
git commit -m "chore: migrate client to merezarezaei/teleframe ecosystem package"
```
