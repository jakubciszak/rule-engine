# Rule Engine PHP Library

Rule Engine is a PHP library that evaluates business logic using three distinct APIs: FlatRuleAPI (Reverse Polish Notation), NestedRuleApi (nested arrays), and StringRuleApi (human-readable expressions). The library supports rule actions and functional programming constructs via the munusphp/munus library.

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## Working Effectively

### Environment Setup
- Install PHP 8.4 or higher (REQUIRED - project will not work with older versions)
- Install Composer for dependency management
- Ensure network access to github.com for dependency downloads

### Bootstrap and Dependencies
- `composer install --no-progress --prefer-dist` -- installs all dependencies including PHPUnit. NEVER CANCEL: May take 5-15 minutes depending on network speed. Set timeout to 30+ minutes.
- If network issues occur with GitHub API rate limits, try: `composer install --ignore-platform-reqs --no-progress`
- If GitHub OAuth token is requested due to rate limits, you can create one at: https://github.com/settings/tokens/new?scopes=&description=Composer+access
- **CRITICAL**: Network connectivity to api.github.com is required for dependency installation. If blocked, dependencies cannot be installed.
- **FALLBACK**: If dependencies fail to install, you can still validate PHP syntax with: `find src -name "*.php" -exec php -l {} \;`

### Build and Validation
- PHP syntax check all files: `find src -name "*.php" -exec php -l {} \;` -- validates syntax without dependencies. Takes 5-10 seconds.
- Full autoloader: `composer dump-autoload` -- regenerates autoloader. Takes 2-5 seconds.

### Running Tests
- `vendor/bin/phpunit` -- runs complete test suite. NEVER CANCEL: Takes 10-30 seconds. Set timeout to 2+ minutes.
- `vendor/bin/phpunit tests/FlatRuleAPITest.php` -- runs specific API tests. Takes 2-5 seconds.
- `vendor/bin/phpunit tests/NestedRuleApiTest.php` -- runs nested API tests. Takes 2-5 seconds.
- `vendor/bin/phpunit tests/StringRuleApiTest.php` -- runs string API tests. Takes 2-5 seconds.
- **NOTE**: If dependencies are not installed due to network issues, tests cannot be run. Focus on syntax validation and code review.

### Manual Testing
Always test functionality after making changes by running complete scenarios:
- Test FlatRuleAPI: Create rules array with elements and evaluate against context data
- Test NestedRuleApi: Create nested rule structures and evaluate with variable data
- Test StringRuleApi: Write human-readable expressions and evaluate with data arrays
- Test rule actions: Verify action execution modifies context data as expected

## Validation Scenarios
After making changes, ALWAYS run through these scenarios to ensure functionality:

### FlatRuleAPI Validation
```php
$rules = [
    'rules' => [
        [
            'name' => 'test_rule',
            'elements' => [
                ['type' => 'variable', 'name' => 'a'],
                ['type' => 'variable', 'name' => 'b'],
                ['type' => 'operator', 'name' => '=='],
            ],
        ],
    ],
];
$context = ['a' => 1, 'b' => 1];
$result = FlatRuleAPI::evaluate($rules, $context);
// Should return true
```

### NestedRuleApi Validation
```php
$rules = ['and' => [
    ['<' => [['var' => 'temp'], 110]],
    ['==' => [['var' => 'status'], 'active']],
]];
$data = ['temp' => 100, 'status' => 'active'];
$result = NestedRuleApi::evaluate($rules, $data);
// Should return true
```

### StringRuleApi Validation
```php
$expr = '.age > 18 and .name is John';
$data = ['age' => 25, 'name' => 'John'];
$result = StringRuleApi::evaluate($expr, $data);
// Should return true
```

### Action Validation
```php
$rules = [
    'rules' => [
        [
            'name' => 'action_test',
            'elements' => [
                ['type' => 'variable', 'name' => 'trigger'],
                ['type' => 'variable', 'value' => true],
                ['type' => 'operator', 'name' => '=='],
            ],
            'actions' => ['.counter + 1'],
        ],
    ],
];
$context = ['trigger' => true, 'counter' => 0];
FlatRuleAPI::evaluate($rules, $context);
// context['counter'] should be 1
```

## Key Project Structure

### Source Code (`src/`)
- `Api/FlatRuleAPI.php` - Reverse Polish Notation rule evaluation
- `Api/NestedRuleApi.php` - Nested array rule evaluation  
- `Api/StringRuleApi.php` - Human-readable expression evaluation
- `Api/ActionParser.php` - Action parsing and execution
- `Rule.php`, `Ruleset.php` - Core rule objects
- `Variable.php`, `Operator.php`, `Proposition.php` - Rule components
- `Action.php`, `ActionType.php` - Action system
- `RuleContext.php` - Evaluation context management

### Tests (`tests/`)
- `FlatRuleAPITest.php` - Comprehensive FlatRuleAPI tests with actions
- `NestedRuleApiTest.php` - NestedRuleApi evaluation tests
- `StringRuleApiTest.php` - String expression parsing and evaluation tests  
- `RuleTest.php`, `RulesetTest.php` - Core object tests
- `VariableTest.php` - Variable comparison and operation tests
- `Fixtures/` - Test helper objects and builders

### Configuration
- `composer.json` - Dependencies: PHP 8.4+, munusphp/munus, PHPUnit 11.4+
- `phpunit.xml` - Test configuration with coverage settings
- `.github/workflows/run-tests.yml` - CI pipeline using PHP 8.4 with standard install/test cycle

## Common Development Tasks

### Adding New Rule Operators
1. Add operator to `Operator.php` enum
2. Implement logic in `Variable.php` comparison methods
3. Add operator to API parsers (`FlatRuleAPI.php`, `NestedRuleApi.php`, `StringRuleApi.php`)
4. Write tests in respective API test files
5. Always test with all three APIs to ensure consistency

### Adding New Action Types  
1. Add type to `ActionType.php` enum
2. Implement execution logic in `Action.php`
3. Update `ActionParser.php` for parsing
4. Test action execution in `FlatRuleAPITest.php`
5. Verify context modification works correctly

### Debugging Rule Evaluation
1. Enable PHPUnit verbose output: `vendor/bin/phpunit --debug`
2. Add temporary debug output in `RuleContext.php` evaluate methods
3. Check variable resolution in `Variable.php` methods
4. Trace operator execution in `Operator.php`
5. Always remove debug code before committing

## Common File Patterns

### Repository Root Files
```
.github/workflows/run-tests.yml  - CI configuration  
.gitignore                      - Excludes vendor/, .idea/, .phpunit.result.cache
LICENSE.md                      - MIT license
README.md                       - Usage examples and documentation
composer.json                   - Project dependencies and autoloading
phpunit.xml                     - Test runner configuration
```

### Critical Dependencies
- `munusphp/munus ^0.16.0` - Functional programming library (Option, Either types)
- `phpunit/phpunit ^11.4` - Testing framework
- No external runtime dependencies beyond PHP 8.4 core extensions

## Known Issues
- **CRITICAL**: Network connectivity to api.github.com is required for `composer install`
- If GitHub API rate limits are hit, installation may require authentication token (see Bootstrap section)
- Environment may lack access to GitHub packages - use syntax validation as fallback
- PHP version must be exactly 8.4+ - earlier versions will fail dependency installation
- Build time is network-dependent, typically 5-15 minutes for fresh install
- In restricted network environments, dependency installation may be impossible - focus on code analysis and syntax validation

## Pre-commit Checklist
- Run `vendor/bin/phpunit` and ensure all tests pass
- Verify syntax: `find src -name "*.php" -exec php -l {} \;`
- Test all three APIs with representative rule examples
- Check that actions execute and modify context correctly
- Ensure no debug code or temporary files remain

## Navigation Tips
- Start with `README.md` for high-level usage patterns
- Check test files for comprehensive usage examples
- Core logic is in `src/Api/` for evaluation entry points
- Rule actions are in `src/Action.php` and `src/ActionType.php`
- Variable operations and comparisons in `src/Variable.php`
- Context management in `src/RuleContext.php`

## IDE and Development Support
- Use PSR-4 autoloading: `JakubCiszak\RuleEngine\` maps to `src/`
- Enable PHP 8.4 syntax highlighting for enum and match expressions
- Tests use standard PHPUnit patterns - follow existing test structure
- All APIs return boolean results from rule evaluation
- Context arrays are passed by reference and modified by actions