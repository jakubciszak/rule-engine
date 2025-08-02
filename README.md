
# Rule Engine

Rule Engine lets you express business logic in plain PHP arrays and evaluate it with ease. 
Pick the API that matches the shape of your data:

- **FlatRuleAPI** – send a linear array in [Reverse Polish Notation](https://en.wikipedia.org/wiki/Reverse_Polish_notation) for fast stack-based evaluation.
- **NestedRuleApi** – describe rules as nested associative arrays that read like infix notation.
- **StringRuleApi** – parse and evaluate human readable infix expressions.

Both APIs accept arrays decoded from JSON and can work with callables inside the evaluation context, giving you a flexible way to run rules or trigger simple actions.

## How it works

The library implements the **Rule Archetype Pattern** from the book ["Enterprise Patterns and MDA: Building Better Software with Archetype Patterns and UML"](https://amzn.eu/d/arcbwKu) by Jim Arlow and Ila Neustadt. Rules are composed of propositions, operators and optional actions. Depending on whether you use `FlatRuleAPI` or `NestedRuleApi`, the rule is converted to a uniform internal structure that the engine evaluates against the provided context.

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

### FlatRuleAPI context

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

This JSON can be decoded and passed to the `FlatRuleAPI` context to get the evaluation result as a boolean. The context array is
passed by reference and updated with any values changed during evaluation.

```php
$rules = json_decode('{"rules": [...]}', true, 512, JSON_THROW_ON_ERROR);
$context = json_decode('{"a":1,"b":2}', true, 512, JSON_THROW_ON_ERROR);
$result = FlatRuleAPI::evaluate($rules, $context);
// $context now contains any modifications made by rule actions
```


### NestedRuleApi format

`NestedRuleApi` accepts rules defined using a JSON structure that resembles infix notation. Operators are written as keys and their arguments are provided in nested arrays.

```php
use JakubCiszak\RuleEngine\Api\NestedRuleApi;

$rules = ['and' => [
    ['<' => [['var' => 'temp'], 110]],
    ['==' => [['var' => 'pie.filling'], 'apple']],
]];

$data = ['temp' => 100, 'pie' => ['filling' => 'apple']];

$result = NestedRuleApi::evaluate($rules, $data); // true
```

You can also pass a set of named rules for evaluation:

```php
$ruleset = [
    'rule1' => ['==' => [['var' => 'a'], 1]],
    'rule2' => ['>' => [['var' => 'b'], 2]],
];

$data = ['a' => 1, 'b' => 3];

NestedRuleApi::evaluate($ruleset, $data); // true
```

### StringRuleApi usage

`StringRuleApi` accepts conditions written as human readable infix expressions. Variables are denoted by a leading dot and resolved from the supplied data array.

```php
use JakubCiszak\RuleEngine\Api\StringRuleApi;

$expr = '(.actualAge > 18 or .name is Adam) or (.citizenship is PL and .actualAge > 15)';
$data = ['actualAge' => 16, 'name' => 'John', 'citizenship' => 'PL'];

$result = StringRuleApi::evaluate($expr, $data); // true
```

Complex nested conditions are also supported:

```php
$complex = '((.a > 1 and (.b < 3 or .c is 2)) or ((.d >= 5 and .e <= 10) and not (.f != 7))) and (.g is true or .h is false)';
$data = [
    'a' => 2,
    'b' => 2,
    'c' => 2,
    'd' => 5,
    'e' => 10,
    'f' => 7,
    'g' => true,
    'h' => true,
];

$result = StringRuleApi::evaluate($complex, $data); // true
```

`StringRuleApi` can evaluate a set of named expressions as a ruleset, returning a single boolean result:

```php
$rules = [
    'adult' => '.actualAge > 18',
    'plCitizen' => '.citizenship is PL',
];
$data = ['actualAge' => 16, 'citizenship' => 'PL'];

$result = StringRuleApi::evaluate($rules, $data); // false
```

Boolean variables can be referenced directly without explicit comparison and negated using `not`:

```php
$flags = '.g and not .h';
$data = ['g' => true, 'h' => false];

StringRuleApi::evaluate($flags, $data); // true
```

### Rule actions

Each rule may include simple actions executed when the rule is evaluated. Actions are expressed as strings:

```
"var.count + 5"
"var.name = John"
"var.total + var.amount"
```

Supported operators are `+` (addition), `-` (subtraction), `.` (concatenation) and `=` (assignment). Values starting with `var.` reference variables from the evaluation context.

When using `NestedRuleApi`, specify actions under the `actions` key alongside the rule expression or within each rule of a ruleset.

#### FlatRuleAPI example

```json
{
  "rules": [
    {
      "name": "rule1",
      "elements": [
        {"type": "variable", "name": "a"},
        {"type": "variable", "name": "b"},
        {"type": "operator", "name": "EQUAL_TO"}
      ],
      "actions": ["var.count + 1"]
    }
  ]
}
```

Evaluating the JSON above with `{ "a": 1, "b": 1, "count": 0 }` updates `count` to `1` when the rule evaluates to `true`.

```php
$rules = json_decode($json, true, 512, JSON_THROW_ON_ERROR); // $json contains JSON above
$context = ['a' => 1, 'b' => 1, 'count' => 0];
FlatRuleAPI::evaluate($rules, $context);
// $context['count'] === 1
```

#### NestedRuleApi example

```php
$ruleset = [
    'rule1' => [
        '==' => [['var' => 'a'], 1],
        'actions' => ['var.count + 1'],
    ],
    'rule2' => [
        '==' => [['var' => 'count'], 1],
    ],
];

$data = ['a' => 1, 'count' => 0];

NestedRuleApi::evaluate($ruleset, $data); // true
// $data['count'] === 1
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
