# wp-env-macos

A drop-in replacement for [`@wordpress/env`](https://www.npmjs.com/package/@wordpress/env)
that runs the **same** wp-env test environment on Apple's native
[`container`](https://github.com/apple/container) runtime via
[opossum](https://github.com/suruseas/opossum) — **no Podman, no Docker Desktop
VM**. (One `sudo` is needed, once, to set up container DNS — see Requirements.)

It brings up the familiar two-site WordPress layout (a dev site you can browse
and a separate tests site that PHPUnit boots against), provisions WordPress
(`wp core install`, plugin/theme activation, `wp-tests-config.php` generation),
and runs your PHPUnit suite — all orchestrated by `wp-env-macos`, a small shell
wrapper around `opossum`.

---

## Why

`@wordpress/env` (wp-env) normally drives `docker compose` (or Podman's
docker-compatibility shim) on top of a Linux VM. On Apple silicon macOS 26+,
Apple ships a native container runtime (`container`) that opossum can orchestrate
with a compose file — eliminating the VM entirely. wp-env itself can't target
`container`/opossum, so this package reproduces wp-env's environment and
provisioning steps on top of opossum instead.

**What stays the same:** your `.wp-env.json`, your PHPUnit config, your tests,
your `npm test` workflow.

**What changes:** the runtime (opossum + `container`) and the orchestration
script (`wp-env-macos`). The first time, you seed `~/.wp-env` with `wp-env-macos
install-wp-tests`, which clones WordPress core and the PHPUnit test framework
directly from GitHub — no `@wordpress/env` dependency at any point.

---

## Requirements

- **macOS 26+ on Apple silicon**
- **Homebrew** (used to install the runtime; see <https://brew.sh>)
- Apple **`container`**, installed and started via Homebrew:
  ```sh
  brew install container
  brew services start container
  ```
- [**opossum**](https://github.com/suruseas/opossum), installed via Homebrew:
  ```sh
  brew install --cask suruseas/opossum/opossum
  ```
- **Container DNS** — set up once so containers resolve each other by bare
  service name (this persists across restarts):
  ```sh
  sudo container system dns create opossum
  ```
  (`wp-env-macos up` also runs this automatically on first start if it's missing.)
- **Python 3** (used by `wp-env-macos` for JSON parsing — present on macOS)

---

## Installation

In your WordPress plugin/theme project (the one that already has a
`.wp-env.json`):

```sh
npm install -D wp-env-macos
```

This installs `wp-env-macos` as a devDependency; `init` (below) adds the npm
scripts for you, so you don't need to edit `package.json` manually. After
installing, the first-time flow is `wp-env-macos install-wp-tests` (seed the
`~/.wp-env` cache from GitHub) → `wp-env-macos init` → `wp-env-macos up` — see
[First-time setup](#first-time-setup).

> If you'd rather set the scripts yourself, the mapping is:
> `env:start`→`wp-env-macos up`, `env:stop`→`wp-env-macos down`,
> `env:install`→`wp-env-macos provision`, and `test`/`test:multisite`/
> `test:coverage`/`test:php80`→`wp-env-macos test[:suffix]`.

---

## First-time setup

### 1. Seed the WordPress cache (once)

wp-env-macos keeps a local cache of WordPress core and the PHPUnit test
framework under `~/.wp-env/<version>/`. Seed it directly from GitHub:

```sh
wp-env-macos install-wp-tests latest     # or a pinned X.Y.Z, or `trunk`
```

This git-clones WordPress core (`WordPress/WordPress`) and the PHPUnit test
framework (`WordPress/wordpress-develop`) into a fresh
`~/.wp-env/wp-env-macos-<version>/` cache, pins the version, and rewrites
`wp-tests-config.php` with the `tests-mysql` DB host. No `@wordpress/env` and
no `svn` required. Long-running steps (git clones, rsyncs) show an animated
spinner with the current phase when run in a terminal (suppressed for
pipes/CI). If the cache is already seeded, re-running `install-wp-tests`
re-pulls in place (a `git fetch` + rsync) rather than re-cloning.

List existing caches with `wp-env-macos cache-list`, and re-pull the pinned version
later with `wp-env-macos fetch` (see [Re-pull with `wp-env-macos fetch`](#re-pull-with-wp-env-macos-fetch)).

### 2. Initialize the project

```sh
npx wp-env-macos init
```

`init` is re-runnable: if `compose.yaml` already exists, `init` automatically
forces the overwrite (printing a notice) rather than refusing — no need to set
`WP_ENV_FORCE_INIT=1` manually. `WP_ENV_FORCE_INIT=1` is still honored if you
prefer to be explicit.

This scaffolds into the current project:

- **`.wp-env.json`** — created if missing (wp-env convention), with
  `"plugins": [ "." ]`; never overwritten once present. `"phpVersion"` here
  selects the container PHP version (see below).
- **`compose.yaml`** — rendered from a template, with your plugin slug, dev
  theme, and any `tests/fixtures/plugins|themes/*` mounts substituted in.
- **`docker/`** — the four build contexts (copied from the package).
- **`.env`** — created if missing (or appended to if it already exists, once,
  idempotently). It **auto-fills `WP_ENV_CACHE_DIR`** when `~/.wp-env`
  contains exactly one cache directory, and prints the path so you can copy it
  in. Re-running `init` re-detects and auto-fills the cache if `WP_ENV_CACHE_DIR`
  isn't set yet, so it converges even if the cache was seeded after the first
  `init`. If multiple cache dirs exist, or none, `init` tells you how to find
  the right one (see step 3).
- **`package.json`** — if present, `init` idempotently adds the
  `env:start`/`env:stop`/`env:install`/`test` scripts (and
  `test:multisite`/`test:coverage`/`test:php80`) pointing at `wp-env-macos`,
  preserving any existing scripts.
- appends `.env`, `.opossum-home/`, `docker/`, and `compose.yaml` to
  `.gitignore` if not already present (all are `init` artifacts).

### 3. Point at the cache

`init` prints the cache dir it detected and, when there's exactly one,
auto-writes `WP_ENV_CACHE_DIR` into `.env` for you — in that case you're done.
`wp-env-macos` reads `.env` automatically on every run, so there's no need to
`source .env` manually. If it could not auto-fill (multiple or no cache dirs),
set `WP_ENV_CACHE_DIR` in `.env` yourself:

- **Multiple cache dirs under `~/.wp-env`:** any cache dir that contains the
  four subdirs `WordPress/`, `tests-WordPress/`, `WordPress-PHPUnit/`, and
  `tests-WordPress-PHPUnit/` will work — the plugin itself is mounted from your
  project (`${PWD}`), not from the cache, so the cache is just generic WordPress
  core + PHPUnit libs. List them with `wp-env-macos cache-list` and use any complete
  one:
  ```sh
  wp-env-macos cache-list
  # pick any dir that has WordPress/, tests-WordPress/, *-PHPUnit/ subdirs
  ```
  Then add that path to your project `.env` (create `.env` if it doesn't
  exist — `init` already scaffolded one) under `WP_ENV_CACHE_DIR`:
  ```sh
  echo "WP_ENV_CACHE_DIR=/Users/you/.wp-env/d0e5a894db5a72bb9cfe0514aeee78fb" >> .env
  ```
- **No `~/.wp-env` yet:** run `wp-env-macos install-wp-tests [version]` first (step 1)
  to populate it. Then re-run `npx wp-env-macos init` — it will now auto-fill
  the path into `.env` for you.

Everything else (ports, host user identity, xdebug host) is auto-detected.

### 4. Choose a PHP version

The containers default to PHP 8.2. To use a different version (matching
wp-env's convention), set `phpVersion` in `.wp-env.json`:

```json
{
	"plugins": [ "." ],
	"phpVersion": "7.4"
}
```

Then re-run `wp-env-macos init` (re-renders `compose.yaml`) and `wp-env-macos up`
(re-builds the images). `WP_ENV_PHP_VERSION` overrides the file when set. The
WordPress/CLI base images are the official `wordpress:php<version>` /
`wordpress:cli-php<version>` images, so any version with a published image
works.

---

## Daily usage

```sh
npm run env:start        # or: npx wp-env-macos up
                          #   → builds images, starts containers, installs WP

# Browse the dev site (real theme, not a fixture stub):
open http://localhost:8888        # admin: admin / password

npm test                         # run the PHPUnit suite (tests site, :8889)
npm run test:multisite
npm run test:coverage            # Xdebug coverage (first-class path)
npm run test:php80

npm run env:stop                 # npx wp-env-macos down
npm run env:stop -- -v           # also drop the database volumes
```

`wp-env-macos up` is idempotent — re-running it re-provisions safely (skips an already
installed site, re-syncs the port in `wp-config.php`, re-activates plugins).

---

## Commands

`wp-env-macos` (alias `mac-env`) supports:

| Command                | Description                                                              |
| ---------------------- | ------------------------------------------------------------------------ |
| `init`                 | Scaffold `compose.yaml` + `.env` + `docker/` into the current project; add `wp-env-macos` scripts to `package.json`; gitignore the artifacts.   |
| `up [opossum args]`    | `opossum up` **then** provision (install WordPress).                     |
| `down [-v]`            | `opossum down` (add `-v` to drop volumes).                               |
| `provision`            | Re-run only the WordPress install/activation step.                       |
| `test`                 | Run PHPUnit on the tests site (`:8889`).                                 |
| `test:multisite`       | Run with `WP_MULTISITE=1`.                                              |
| `test:coverage`        | Run with Xdebug coverage flags.                                         |
| `test:php80`           | Run the PHP 8.0 variant (if your matrix includes it).                   |
| `install-wp-tests [v]`  | Seed the `~/.wp-env` cache from git — `v` is `X.Y.Z` \| `trunk` \| `latest` (default `latest`). No wp-env needed. |
| `fetch`                | Re-pull the pinned WordPress version in place (`git fetch`/pull + rsync). Manual — never run by `up`. |
| `cache-list`           | List `~/.wp-env/*` cache dirs with their pinned version.            |
| `help` / `--help`     | Print the full command list.                                            |
| `ps`, `logs`, `exec`, `run`, `config`, `build`, … | Passed straight through to `opossum`. |

Any unrecognized command is forwarded to `opossum -f compose.yaml`, so you can
use the full opossum CLI (`wp-env-macos exec cli wp plugin list`, etc.).

---

## Configuration

All optional — sensible defaults apply. Set them in `.env` or the environment.

| Variable                   | Default              | Purpose                                                          |
| -------------------------- | -------------------- | ---------------------------------------------------------------- |
| `WP_ENV_CACHE_DIR`         | *(required)*         | Path to the git-seeded `~/.wp-env/<version>` cache.               |
| `WP_ENV_PORT`              | `8888`               | Dev site port (matches wp-env).                                  |
| `WP_ENV_TESTS_PORT`        | `8889`               | Tests site port (matches wp-env).                                |
| `WP_PLUGIN_SLUG`           | project dir name     | Plugin directory under `wp-content/plugins` (mount + phpunit path). |
| `WP_ENV_DEV_THEME`         | `twentytwentyfive`  | Theme activated on the **dev** site so it renders a real page.   |
| `WP_ENV_FIXTURE_PLUGINS`   | `tests/fixtures/plugins/*` | Space-separated host paths mounted as fixture plugins.      |
| `WP_ENV_FIXTURE_THEMES`    | `tests/fixtures/themes/*`  | Space-separated host paths mounted as fixture themes.      |
| `HOST_USERNAME`/`HOST_UID`/`HOST_GID` | auto (`id`) | Container user identity for bind mounts.                     |
| `XDEBUG_HOST`              | `host.docker.internal` | Host Xdebug connects back to for coverage debugging.          |

> **Concurrent projects:** only one stack can be active at a time on a given
> host (Apple `container` binds host ports directly, and opossum's
> per-project namespacing does not isolate host ports — same as two wp-env
> instances would collide). Stop one (`wp-env-macos down`) before starting another.
> Because you run one at a time, the standard 8888/8889 ports work for every
> project.

---

## How it works

- **Two WordPress installs**, each with its own database:
  - `wordpress` + `cli` + `mysql` → the **dev site** on `:8888`.
  - `tests-wordpress` + `tests-cli` + `tests-mysql` → the **tests site** on
    `:8889`, bootstrapped fresh by PHPUnit.
- **Database reachability via container DNS:** `wp-env-macos up` runs
  `sudo container system dns create opossum` once (idempotent) so the app
  containers reach the database by its bare service name (`mysql` /
  `tests-mysql`) on the internal port 3306 — no host LAN IP and no published
  DB ports required.
- **Provisioning mirrors wp-env's `configureWordPress`:** `wp core install`
  (idempotent), `wp config set` from `.wp-env.json`, plugin/theme activation,
  and generating `wp-tests-config.php` from the cache's
  `wp-tests-config-sample.php`, with the DB host set to `tests-mysql` and the
  port normalized to `WP_ENV_TESTS_PORT`.
- **Xdebug** is compiled into the CLI images at build time, so
  `test:coverage` works without any runtime hook.

---

## Re-pull with `wp-env-macos fetch`

`wp-env-macos up` never does network I/O. To refresh the already-pinned version in
place (a fast `git fetch`/`pull` + rsync — no re-download):

```sh
wp-env-macos fetch          # re-pulls the version pinned in <cache>/.wp-env-version
```

To **switch** versions, re-run `wp-env-macos install-wp-tests <new-version>` — it
re-syncs the cache in place and writes a new `.wp-env-version` (no separate cache
dir is created).

## List caches with `wp-env-macos cache-list`

```sh
wp-env-macos cache-list     # lists ~/.wp-env/* with their pinned version
# e.g.
#   /Users/you/.wp-env/wp-env-macos-6.7      6.7        (git)
```

Handy when several cache dirs exist and `init` can't auto-detect one
(it only auto-fills when exactly one exists).

---

## Troubleshooting

**`wp-tests-config.php not found`** — the `~/.wp-env` cache is empty. Run
`wp-env-macos install-wp-tests` to populate it, then `wp-env-macos up`.

**Dev site redirects to the wrong port / blank page** — wp-env bakes
`WP_SITEURL`/`WP_HOME` into the cache `wp-config.php` at install time. `wp-env-macos
provision` re-syncs these to the current port and activates a real theme
(`WP_ENV_DEV_THEME`) so the page renders. If a browser cached an old redirect,
hard-refresh (⌘-Option-R) or clear the site's cache.

**Port already in use** — another stack (or an old Podman/wp-env instance) is
holding 8888/8889/3306/3307. Stop it, or run only one opossum project at a time.

**`command not found: opossum`** — install opossum and ensure it's on your
`PATH`. Set `OPOSSUM=/path/to/opossum` if it's elsewhere.

**Containers won't resolve each other by name** — run
`sudo container system dns create opossum` once (idempotent). After that, the
app containers reach the database by its bare service name (`mysql` /
`tests-mysql`) on the internal port 3306. `wp-env-macos up` runs this automatically
on first start if it's missing.

---

## Publishing

The package is published to npm as `wp-env-macos` (macOS/Apple-silicon
only — `os`/`cpu` are constrained in `package.json`). From the package root:

```sh
npm login          # browser auth (or `npm adduser`)
npm version patch  # bump (patch/minor/major) — also git-tags
npm publish        # --access public if your account defaults to restricted
```

`npm pack --dry-run` previews the exact tarball (whitelisted by the `files`
field: `bin/`, `docker/`, `templates/`, `README.md`, `package.json`).

---

## Local development (testing an unpublished change)

To use a working copy of the package in another project without publishing:

**Option A — install from a local path:**
```sh
cd /path/to/your-plugin
npm install -D /abs/path/to/wp-env-macos
# (after a release: npm install -D wp-env-macos)
```

**Option B — `npm link` (edits propagate live):**
```sh
cd /path/to/wp-env-macos && npm link
cd /path/to/your-plugin && npm link wp-env-macos
```

Then `npx wp-env-macos init` resolves to the linked copy. If `npm install`
reports "up to date" and skips the local package, remove `package-lock.json` +
`node_modules` and reinstall — a stale lockfile pin to a previous `file:` path
can block re-resolution.

---

## License

GPL-3.0-or-later.
