
# Copilot Instructions for rule-engine

**ALWAYS follow these instructions first and fallback to additional search and context gathering only if the information in the instructions is incomplete or found to be in error.**

These instructions guide GitHub Copilot (coding agent) when working in this repository after a fresh clone. They define the exact commands, timing expectations, and validation steps needed for effective development.

## Repository Overview

**Language:** PHP 8.4
**Type:** Composer library implementing the Rule Archetype Pattern
**Architecture:** DDD-inspired modules with immutable domain objects
**Standards:** PSR-12, 4-space indentation, `declare(strict_types=1);` at top of every PHP file

## Essential First Steps After Fresh Clone

### 1. Install Dependencies
```bash
composer install --ignore-platform-reqs --no-interaction
```
**TIMING:** Takes approximately 18-20 seconds. NEVER CANCEL.
**ISSUES:** May encounter GitHub authentication errors - uses fallback git cloning automatically.
**NOTE:** `--ignore-platform-reqs` required if not using PHP 8.4 exactly.

### 2. Validate Installation
```bash
vendor/bin/phpunit --version
```
**EXPECTED OUTPUT:** PHPUnit 11.5.34 by Sebastian Bergmann and contributors.

### 3. Run Test Suite
```bash
vendor/bin/phpunit
```
**TIMING:** Completes in 0.04 seconds. NEVER CANCEL.
**EXPECTED:** 42 tests, 90 assertions, all passing
**OUTPUT FORMAT:** Use `vendor/bin/phpunit --testdox` for detailed test descriptions

### 4. Validate Examples Structure
```bash
php Examples/validate_examples.php
```
**TIMING:** Completes in 0.04 seconds. NEVER CANCEL.
**PURPOSE:** Verifies all KYC example files exist and have proper structure without requiring dependencies

## Working Effectively

### Build and Test Workflow
Always run this sequence before making changes:
```bash
composer install --ignore-platform-reqs --no-interaction
vendor/bin/phpunit
php Examples/validate_examples.php
```
**TOTAL TIME:** Under 25 seconds. NEVER CANCEL any of these commands.

### Development Commands
- **Run specific test:** `vendor/bin/phpunit tests/SpecificTest.php`
- **Run with coverage:** `vendor/bin/phpunit --coverage-text`
- **Syntax check:** `php -l src/SomeFile.php`
- **Check autoloader:** `composer dump-autoload`

### Static Analysis (Not Currently Configured)
PHPStan is mentioned in existing instructions but not currently installed. To add it:
```bash
composer require --dev phpstan/phpstan
vendor/bin/phpstan analyze src
```
**WARNING:** Not part of current CI pipeline - only add if explicitly requested.

## Validation Scenarios

### CRITICAL: Always Test Rule Engine Functionality
After making any changes to core rule engine logic, validate with:

1. **Basic API functionality:**
```bash
vendor/bin/phpunit tests/FlatRuleAPITest.php
vendor/bin/phpunit tests/NestedRuleApiTest.php
vendor/bin/phpunit tests/StringRuleApiTest.php
```

2. **Example structure validation:**
```bash
php Examples/validate_examples.php
```

### WARNING: Example Runtime Issues
The KYC examples in `Examples/KYC/` have runtime errors when executed:
```bash
php Examples/KYC/BasicRiskScoring.php
```
**KNOWN ISSUE:** Fatal error on `Proposition::equalTo()` method
**ACTION:** Do NOT attempt to run the KYC examples directly - they are structural examples only
**VALIDATION:** Use `php Examples/validate_examples.php` instead

## Key Repository Locations

### Source Code (`src/`)
- **`src/Api/`** - Main APIs: FlatRuleAPI, NestedRuleApi, StringRuleApi
- **`src/Exception/`** - Custom exceptions
- **`src/*.php`** - Core domain objects: Rule, Ruleset, Variable, Proposition

### Tests (`tests/`)
- **Complete test coverage** - 42 tests covering all APIs and core functionality
- **Test structure:** One test file per main class
- **Test timing:** Entire suite runs in ~0.04 seconds

### Examples (`Examples/`)
- **`Examples/KYC/`** - Real-world KYC onboarding scenarios (structural only)
- **`Examples/README.md`** - Documentation and usage instructions
- **`Examples/validate_examples.php`** - Structure validation script

### Configuration
- **`composer.json`** - Dependencies and autoloading
- **`phpunit.xml`** - PHPUnit configuration
- **`.github/workflows/run-tests.yml`** - CI pipeline

## Development Guidelines

### PHP Code Standards
Always start PHP files with:
```php
<?php
declare(strict_types=1);
```

### Required Practices
- Use explicit parameter and return types everywhere
- Prefer `final` classes and immutability in domain objects
- Keep constructors small
- No global state or singletons

### Testing Requirements
- Every behavioral change must include tests
- Use descriptive test names: `test_it_evaluates_true_when_condition_met`
- Cover edge cases: nulls, wrong types, boundary values
- Prefer unit tests over integration tests

## Common Tasks

### Add New Rule Operator
1. Create operator class in `src/`
2. Add corresponding test in `tests/`
3. Update API classes if needed
4. Run full test suite
5. **Expected time:** 30-60 minutes including tests

### Fix Rule Evaluation Logic
1. Identify failing test or create new test
2. Modify core logic in `src/Rule.php` or related classes
3. Ensure all existing tests still pass
4. **Expected time:** 15-30 minutes

### Add New API Method
1. Extend appropriate API class in `src/Api/`
2. Create comprehensive test coverage
3. Update README.md if public API
4. **Expected time:** 45-90 minutes

## Troubleshooting

### Composer Install Fails
- **GitHub auth errors:** Use `--no-interaction` flag
- **Platform requirements:** Use `--ignore-platform-reqs` flag
- **Network timeouts:** Wait for git fallback (automatic)

### Tests Fail
- **PHPUnit not found:** Run `composer install` first
- **Autoload errors:** Run `composer dump-autoload`
- **Memory issues:** Increase PHP memory limit

### Examples Don't Run
- **Expected behavior:** Examples have runtime errors
- **Correct validation:** Use `php Examples/validate_examples.php`
- **Do not attempt:** Running KYC examples directly

## CI Pipeline

GitHub Actions runs:
```yaml
- PHP 8.4 setup
- composer install
- vendor/bin/phpunit
```
**Timing:** Entire CI takes ~2-3 minutes
**NEVER CANCEL:** All CI commands have appropriate timeouts

## Ready-Made Commands Reference

```bash
# Essential setup (run once after clone)
composer install --ignore-platform-reqs --no-interaction

# Development workflow (run before/after changes)
vendor/bin/phpunit
php Examples/validate_examples.php

# Test specific components
vendor/bin/phpunit tests/FlatRuleAPITest.php
vendor/bin/phpunit tests/NestedRuleApiTest.php  
vendor/bin/phpunit tests/StringRuleApiTest.php

# Check syntax
php -l src/SomeFile.php

# Repository exploration
cat README.md
cat Examples/README.md
ls src/Api/
```

## Critical Timing and Timeout Guidelines

- **composer install:** 18-20 seconds, timeout: 60 seconds
- **vendor/bin/phpunit:** 0.04 seconds, timeout: 30 seconds
- **Example validation:** 0.04 seconds, timeout: 30 seconds
- **CI pipeline:** 2-3 minutes total

**NEVER CANCEL** any build or test command. If commands appear to hang, wait at least 60 seconds before considering alternatives.

## Acceptance Criteria for Changes

Before requesting review, verify:
- [ ] `composer install` completes successfully
- [ ] `vendor/bin/phpunit` passes all 42 tests
- [ ] `php Examples/validate_examples.php` reports all files valid
- [ ] New/changed behavior has test coverage
- [ ] Code follows strict typing and final class patterns
- [ ] No new runtime dependencies added without explicit instruction
- [ ] Public API unchanged or migration path documented

