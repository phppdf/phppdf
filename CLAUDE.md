# phppdf

## Execution
- To run php: `./scripts/php`
- To run unit tests: `./scripts/composer test:coverage`

## Code Principles
- Apply SOLID principles where they improve maintainability
- Prefer simple solutions over clever abstractions (KISS)
- Avoid duplication, but do not abstract prematurely (DRY)
- Favor composition over inheritance
- Keep classes focused on a single responsibility
- Keep functions small and deterministic where possible
- Prefer explicitness over magic
- Optimize for readability and maintainability first
- Avoid unnecessary dependencies and frameworks

## Testing
- All unit tests should follow the AAA pattern.
- The base namespace for unit tests is: `PhpPdf\`.
- Aim for 100% code coverage while writing unit tests.
- Unit tests should be in the path: tests/unit/{Namespace}/{Class}/{Method}Test.php
- Each method has its own test class with a `CoversClass` and a `CoversMethod` attribute.
- All functionalities should be backed by unit tests and benchmarks.
- Benchmarks should be in the path: tests/benchmarks/{Namespace}/{Class}/{Method}Bench.php
