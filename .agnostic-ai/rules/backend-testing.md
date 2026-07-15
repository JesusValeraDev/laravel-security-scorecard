---
globs: tests/**/*.php
---

# Testing Conventions

## Coverage Standards

- **Minimum 80% coverage** for all new code
- Domain layer should have **95%+ coverage**
- Critical business logic requires **100% coverage**

## TDD Workflow

Red-green-refactor:

1. **RED**: Write failing test first
2. **GREEN**: Minimum code to pass
3. **REFACTOR**: Improve while green
4. **VERIFY**: `./vendor/bin/phpunit --coverage-text`

## Test Commands

```bash
composer test                                       # Full suite: Pint (lint) + PHPStan + config:clear + PHPUnit
./vendor/bin/phpunit                                # Run tests only (skip lint/phpstan)
./vendor/bin/phpunit --testsuite=Unit               # Unit tests only
./vendor/bin/phpunit --testsuite=Integration        # Integration tests only
./vendor/bin/phpunit --testsuite=Feature            # Feature tests only
./vendor/bin/phpunit --filter=AnnotationTest        # Run specific test class
```

## Test Naming Conventions

| Test Type         | File Pattern                                                                        |
|-------------------|-------------------------------------------------------------------------------------|
| Entity Test       | `tests/Unit/{Module}/Domain/Entity/{Name}Test.php`                                  |
| Value Object Test | `tests/Unit/{Module}/Domain/ValueObject/{Name}Test.php`                             |
| Handler Test      | `tests/Unit/{Module}/Application/{Type}/{Name}HandlerTest.php`                      |
| Repository Test   | `tests/Integration/{Module}/{Name}RepositoryTest.php`                               |
| Feature Test      | `tests/Feature/{Module}/{Name}Test.php`                                             |
| Model Factory     | `modules/{Module}/Infrastructure/Persistence/Eloquent/Model/{Name}ModelFactory.php` |

## Test Method Naming

```php
// Method: test_<what>_<condition>_<expected>
public function test_create_user_with_valid_email_returns_user(): void
public function test_create_user_with_invalid_email_throws_exception(): void

// Or use descriptive it_* naming
public function it_creates_a_user_with_valid_data(): void
public function it_throws_when_email_is_invalid(): void
```

## Mandatory Test Paths

**Every new piece of code MUST ship with tests covering these three paths:**

### 1. Happy Path (required)

Expected successful use case.

```php
#[Test]
public function test_create_annotation_with_valid_data(): void
{
    $this->handler->__invoke(new CreateAnnotation(
        id: Uuid::generate()->value(),
        chapterId: ChapterId::generate()->value(),
        selectedText: 'The river was wider',
        note: 'Nice metaphor',
        color: 'blue',
    ));

    $annotation = $this->repository->findById(...);
    $this->assertNotNull($annotation);
    $this->assertSame('The river was wider', $annotation->selectedText());
}
```

### 2. Edge Cases (required when they exist)

Boundaries, defaults, empty-but-valid inputs, trimming, type coercions.

```php
#[Test]
public function test_create_annotation_with_defaults(): void { /* color defaults to yellow, note defaults to '' */ }

#[Test]
public function test_create_annotation_trims_whitespace(): void { /* '  text  ' → 'text' */ }

#[Test]
public function test_update_note_allows_empty_string(): void { /* clearing a note is valid */ }
```

### 3. Sad Path (required)

Invalid inputs, not-found cases, authorization failures.

```php
#[Test]
public function test_create_annotation_throws_when_selected_text_is_empty(): void
{
    $this->expectException(\InvalidArgumentException::class);
    Annotation::create(id: ..., chapterId: ..., selectedText: '');
}

#[Test]
public function test_update_throws_when_annotation_not_found(): void
{
    $this->expectException(AnnotationNotFoundException::class);
    $this->handler->__invoke(new UpdateAnnotation(id: 'nonexistent', note: 'x'));
}
```

### Checklist before marking tests complete

- [ ] At least one happy path test per public method/handler
- [ ] Edge cases for defaults, trimming, boundary values, optional fields
- [ ] Sad path for every validation rule, not-found case, and authorization check
- [ ] No test depends on another test's state (isolated via `setUp()`)

## Rules

- Use `#[Test]` attribute (not `/** @test */` docblock)
- Use `mock()` directly instead of `Mockery::mock()`
- Use InMemory repository implementations for unit tests
- Use `NullTransactionManager` for handlers that need `TransactionManager`
- Invoke handlers with `$this->handler->__invoke($command)` syntax

## Unit Test Pattern (Handler)

```php
final class CreateOrderHandlerTest extends TestCase {
    private OrderInMemoryRepository $repository;
    /** @var DomainEvent[] */
    private array $dispatchedEvents = [];
    private CreateOrderHandler $handler;

    protected function setUp(): void {
        $this->repository = new OrderInMemoryRepository;
        $this->dispatchedEvents = [];

        $eventDispatcher = new class($this->dispatchedEvents) implements EventDispatcher {
            /** @param DomainEvent[] $events */
            public function __construct(private array &$events) {}
            public function dispatch(DomainEvent $event): void { $this->events[] = $event; }
        };

        $this->handler = new CreateOrderHandler(
            $this->repository,
            $eventDispatcher,
            new NullTransactionManager,
        );
    }

    #[Test]
    public function creates_order(): void {
        $command = new CreateOrder(id: Uuid::generate()->value(), ...);

        $result = $this->handler->__invoke($command);

        $this->assertSame(Status::DRAFT, $result->status());
        $this->assertCount(1, $this->dispatchedEvents);
        $this->assertInstanceOf(OrderCreated::class, $this->dispatchedEvents[0]);
    }
}
```

- `setUp()` creates InMemory repo + anonymous `EventDispatcher` + `NullTransactionManager`
- Anonymous class with `&$events` reference captures dispatched events
- Assert on domain entity state + dispatched events

## InMemory Repository Pattern

```php
final class UserInMemoryRepository implements UserRepository {
    /** @var array<string, User> */
    private array $users = [];

    public function save(User $user): void {
        $this->users[$user->id()->value()] = $user;
    }

    public function findById(UserId $id): ?User {
        return $this->users[$id->value()] ?? null;
    }
}
```

Located at `modules/{Module}/Infrastructure/Persistence/InMemory/`.

## Feature Test Pattern

```php
final class OrderControllerTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        // Create test data via domain factories
    }

    #[Test]
    public function shows_order_page(): void {
        $this->actingAs($this->user)
            ->get('/orders')
            ->assertOk();
    }
}
```

Uses `RefreshDatabase` trait for real DB interaction.

## Debugging Failing Tests

1. Read the error message carefully
2. Check test isolation (no shared state)
3. Verify mock implementations
4. Change production code, not tests (unless tests are wrong)
5. Use `/tdd-cycle` for guided assistance

## What to Test

| Layer          | Test Focus                                                  |
|----------------|-------------------------------------------------------------|
| Domain         | Value object validation, entity invariants, domain services |
| Application    | Handler behavior, input validation, output mapping          |
| Infrastructure | Repository queries, external service integration            |

## What NOT to Test

- Framework code (Laravel handles its own testing)
- Simple getters/setters without logic
- Private methods (test via public interface)
- Third-party library internals

## Coverage Configuration

`phpunit.xml` `<source>` includes only `app/`; `modules/` is not in the default coverage source — run
`./vendor/bin/phpunit --coverage-text` to see what is measured. No exclude block exists in `phpunit.xml`.
