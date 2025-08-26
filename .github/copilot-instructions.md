
# Copilot Instructions for rule-engine

These instructions guide GitHub Copilot (coding agent) when working in this repository. They define scope, guardrails, preferred workflows, and acceptance criteria so the agent can produce small, high-quality pull requests.

## Repository context

Language: PHP 8.4

Standards: PSR-12, 4-space indentation, declare(strict_types=1); at the top of every PHP file

Architecture: DDD-inspired modules, prefer immutability in domain layer (value objects, readonly where possible), small and composable services

Packaging: Composer library

Testing: PHPUnit

Static analysis: PHPStan (high level)

CI: GitHub Actions run tests and static analysis


## Rules of engagement

1. Prefer small, incremental PRs that touch a limited area of code.


2. Do not introduce new runtime dependencies without an explicit instruction in the task.


3. Do not change public API or DSL semantics unless the task explicitly asks for it. If a change is necessary, propose it in the PR description with a migration path.


4. Always add or update tests that cover new or changed behavior.


5. Keep static analysis clean - no new PHPStan issues.


6. Follow repository conventions strictly: strict types, full parameter and return types, final where sensible, readonly data where applicable.


7. Keep documentation in sync. If behavior changes, update docs/ and examples.



## High-signal tasks for the agent

Add or extend unit tests for evaluators, conditions, and DSL parsing.

Improve error handling and messages, including negative test cases.

Small refactors that remove duplication or dead code without changing public API.

Documentation improvements with runnable examples in examples/.


Avoid: large architectural rewrites, sweeping renames, or cross-cutting changes in a single PR.

Project layout to rely on

src/ - production code organized by domain/module

tests/ - unit and integration tests

docs/ - DSL and usage documentation

examples/ - small runnable examples


Read before coding:

README.md

docs in docs/ relevant to the touched module


### Environment and commands

Before making changes, ensure a clean baseline:

composer install
composer test
composer phpstan

CI will run the same commands. Do not modify CI workflows unless explicitly asked.

### Coding guidelines

Always start PHP files with:

```php
<?php
declare(strict_types=1);
```

Use explicit parameter and return types everywhere.

Prefer final classes and immutability in domain objects.

Keep constructors small; extract validation to dedicated methods where appropriate.

No use of global state or singletons in new code.


## Test policy

Every behavioral change must include tests.

Use descriptive, behavior-oriented test names:

test_it_evaluates_true_when_left_is_greater_than_right


Cover edge cases and invalid inputs (nulls, wrong types, boundary values).

Favor pure, isolated unit tests. Use integration tests only when IO or external protocols are involved.


## Static analysis policy

Run composer phpstan locally and keep the report clean.

If suppression is unavoidable, scope it as narrowly as possible and add a short justification comment.


## Pull request checklist

The agent must verify all items before requesting review:

[ ] Code follows repo style: strict types, typed signatures, final where sensible

[ ] New or changed behavior covered by tests

[ ] composer test passes locally

[ ] composer phpstan passes with no new issues

[ ] Public API and DSL are unchanged, or changes are documented with migration notes

[ ] Docs updated if behavior or DSL changed

[ ] No new runtime dependencies were added without explicit instruction


## Commit and PR conventions

Keep commits focused; use Conventional Commits where reasonable:

feat: add >= and <= comparison evaluator

fix: handle null right operand in GreaterThan

docs: clarify precedence of boolean operators


## PR description must include:

Problem statement and scope

Summary of changes

Test coverage summary

Impact on public API or DSL (none or detailed)

Risk and rollback notes if applicable



## Ready-made task templates

1) Add unit tests for comparison operators

Goal:

Add tests for >, >=, <, <= including boundaries and invalid inputs.


## Acceptance:

[ ] New test file (e.g., tests/Evaluator/ComparisonOperatorsTest.php)

[ ] Edge cases covered: equality, negatives, floats

[ ] Invalid inputs covered: null, non-numeric strings

[ ] No new PHPStan issues


2) Improve DSL parser error messages

Goal:

Unify exception messages and add simple error codes.


Acceptance:

[ ] Parser throws domain exceptions with clear messages and codes

[ ] Tests for the 3 most common syntax errors

[ ] docs/dsl.md updated with error reference


3) Dead code removal in src/Rule/

Goal:

Remove unused private methods and add final where sensible.


Acceptance:

[ ] No public API changes

[ ] Tests and PHPStan green

[ ] No behavior change


Guardrails and anti-goals

Do not change file headers, license statements, or package metadata unless asked.

Do not add code generators or eval-based features.

Do not reformat the entire codebase in one PR.

Do not weaken validations to make tests pass - fix the code or the tests correctly.


Review expectations

The agent should be responsive to review comments, pushing focused follow-ups.

If a requested change is ambiguous, ask for clarification in the PR comments and propose a concrete option.

