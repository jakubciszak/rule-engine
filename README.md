
# Rule Engine

Rule Engine is a PHP library designed to evaluate complex rules and propositions. It supports various operators and can handle propositions and variables.

## Requirements

- PHP 8.3 or higher
- Composer

## Installation

To install the library, use Composer:

```sh
composer require jakubciszak/rule-engine
````

## Usage

### Notation
This implementation use [Reverse Polish Notation (RPN)](https://en.wikipedia.org/wiki/Reverse_Polish_notation). \
**RPN** is a mathematical notation in which operators follow their operands. This notation eliminates the need for parentheses that are used in standard infix notation, making the evaluation of expressions simpler and more efficient.

For example, the expression \
`(2 + 3) * 5` \
in standard notation would be written as \
`2 3 + 5 *` in RPN.

In this notation, you first add 2 and 3 to get 5, and then multiply by 5 to get 25.

The Rule Engine uses RPN to simplify the process of building conditions, making it more intuitive to construct complex logical expressions.

### Creating Rules

You can create rules using the provided methods for different operators:

```php
use JakubCiszak\RuleEngine\Rule;
use JakubCiszak\RuleEngine\Operator;

$rule = (new Rule())
    ->variable('expectedAge', 22)
    ->variable('age')
    ->greaterThan()
    ->evaluate($context);
```

### Evaluating Rules

To evaluate a rule, you need to provide a `RuleContext`:

```php
use JakubCiszak\RuleEngine\RuleContext;

$context = new RuleContext();
$result = $rule->evaluate($context);
```

## Development

### Running Tests

To run the tests, use PHPUnit:

```sh
vendor/bin/phpunit
```

## Contributing

Contributions are welcome! Please open an issue or submit a pull request.

## License

This project is licensed under the MIT License.

## Authors

- Jakub Ciszak - [j.ciszak@gmail.com](mailto:j.ciszak@gmail.com)


## Additional Information

- The project uses the `munusphp/munus` library for functional programming constructs.
- The source code is located in the `src/` directory.
- Tests are located in the `tests/` directory.
