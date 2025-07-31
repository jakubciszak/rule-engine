
# Rule Engine

Rule Engine is a PHP library designed to evaluate complex rules and propositions. It supports various operators and can handle propositions and variables.

This model is based on rule archetype pattern from book ["Enterprise Patterns and MDA: Building Better Software with Archetype Patterns and UML"](https://amzn.eu/d/arcbwKu) by Jim Arlow and Ila Neustadt.



## Requirements

 - PHP 8.4 or higher
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

### JsonRPN context

Rules can also be defined using JSON in RPN order. The example below presents two rules:

```json
{
  "rules": [
    {
      "name": "rule1",
      "elements": [
        {"type": "variable", "name": "a"},
        {"type": "variable", "name": "b"},
        {"type": "operator", "name": "EQUAL_TO"}
      ]
    },
    {
      "name": "rule2",
      "elements": [
        {"type": "variable", "name": "amount"},
        {"type": "variable", "name": "max"},
        {"type": "operator", "name": "GREATER_THAN"}
      ]
    }
  ]
}
```

This JSON can be passed to the `JsonRPN` context to get the evaluation result in the same structure.

### JsonRule format

`JsonRule` accepts rules defined using a JSON structure that resembles infix notation. Operators are written as keys and their arguments are provided in nested arrays.

```php
use JakubCiszak\RuleEngine\Api\JsonRule;

$rules = ['and' => [
    ['<' => [['var' => 'temp'], 110]],
    ['==' => [['var' => 'pie.filling'], 'apple']],
]];

$data = ['temp' => 100, 'pie' => ['filling' => 'apple']];

$result = JsonRule::evaluate($rules, $data); // true
```

You can also pass a set of named rules for evaluation:

```php
$ruleset = [
    'rule1' => ['==' => [['var' => 'a'], 1]],
    'rule2' => ['>' => [['var' => 'b'], 2]],
];

$data = ['a' => 1, 'b' => 3];

JsonRule::evaluate($ruleset, $data); // true
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
