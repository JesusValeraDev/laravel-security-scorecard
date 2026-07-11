# Event Safety Checklist (AI Workflow Code to Spec)

## Semantics
- [ ] Delivery assumption declared
- [ ] Duplicate handling explained
- [ ] Out-of-order behavior defined
- [ ] Idempotency key defined

## State Transitions
- [ ] Valid transitions listed
- [ ] Invalid transitions defined (NOOP vs error)
- [ ] Race conditions considered

## Retry & Recovery
- [ ] Retry policy stated
- [ ] Retryable vs non-retryable errors classified
- [ ] DLQ / parking strategy described when applicable
- [ ] Replay safety described

## Compatibility
- [ ] Versioning strategy defined
- [ ] Dual handling if renaming events
- [ ] Rollback safety

## Observability
- [ ] event_id logged
- [ ] aggregate_id logged
- [ ] idempotency key logged
- [ ] retry count logged
