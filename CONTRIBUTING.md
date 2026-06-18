# Contributing

Before making a change, please open an issue or discuss it via email with the repository owners. This avoids wasted effort if the change doesn't align with the project direction.

Please follow our [Code of Conduct](https://github.com/nicumicle/simple-jwt-login/blob/master/CODE_OF_CONDUCT.md) in all interactions.

## Local Environment Setup

### Requirements

- git
- docker
- docker compose

### Starting the environment

1. Clone the repository:
   ```bash
   git clone https://github.com/nicumicle/simple-jwt-login.git
   cd simple-jwt-login
   ```

2. Start the Docker containers:
   ```bash
   docker compose -f docker/docker-compose.yaml up
   ```

3. Open `http://localhost:88/wp-admin` in your browser and complete the WordPress setup.

The project directory is mounted into the container at `/var/www/dev`, and the plugin is symlinked into `wp-content/plugins/simple-jwt-login` automatically.

## Running Tests

Connect to the container first:

```bash
docker exec -it wordpress /bin/bash
cd /var/www/dev
```

Then run the suite you need:

```bash
composer run tests            # Unit tests with coverage
composer run tests-feature    # Feature/integration tests (requires wpdb on port 3308)
composer run tests-wp         # WP integration tests (requires full WP test environment)
```

Run a single test file or method:

```bash
vendor/bin/phpunit --filter testMethodName
vendor/bin/phpunit tests/Unit/Services/LoginServiceTest
```

## Code Quality

Run all checks before submitting a PR:

```bash
composer run check-plugin
```

This runs (in order):
- `phpcs` - PSR-2/PSR-12 style check
- `phpmd` - design, clean code, unused code, and naming convention checks
- `phpstan` - static analysis at level 5
- Unit tests
- Feature tests

Individual commands:

```bash
composer run phpcs            # Style check only
composer run lint             # Auto-fix style issues
composer run phpstan          # Static analysis only
composer run phpmd-design     # PHPMD design violations
```

## Code Standards

- Follow PSR-2/PSR-12 style (enforced by PHPCS)
- Keep PHP 5.5 compatibility in `src/`, `routes/`, and `views/` - no `??`, scalar type hints, return types, or `random_bytes()`
- Use `protected` visibility for methods by default
- Use `assertSame` over `assertEquals` in tests
- Use `#[DataProvider]` for parameterized tests instead of duplicating test methods
- Never use `var_dump`, `print_r`, `die`, `exit`, or `eval`
- Always pass `true` as the third argument to `in_array`

See `CLAUDE.md` for the full coding standards and architecture overview.

## Pull Request Process

1. Open an issue or discuss the change before starting work.
2. Write tests for all new code. The project targets 80% coverage.
3. Run `composer run check-plugin` and ensure all checks pass.
4. Fill in the PR template, including the issue link and a description of what you tested.

PRs that fail CI or lack tests will not be merged.

## Reporting Bugs

Use the [bug report template](https://github.com/nicumicle/simple-jwt-login/issues/new?template=bug_report.md). Include your plugin version, PHP version, and WordPress version.

## Requesting Features

Use the [feature request template](https://github.com/nicumicle/simple-jwt-login/issues/new?template=feature_request.md).
