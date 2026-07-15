---
name: verify-dont-trust
description: Read actual files before making claims about the codebase — never assume, infer, or trust stale docs
alwaysApply: true
---

# Verify, Don't Trust

Factual claims about this codebase must be backed by a file read in *this conversation*. No inferring, guessing, or
paraphrasing from memory, issues, PRs, ADRs, or comments.

Treat "is this really X?", "how does this work?", "is this issue valid?" as verify-first. Open files before opining.
Trace call chains end-to-end (controller → handler → repository → API/DB). Cite the file+line you actually read.

If not locally verifiable (runtime, framework internals, third-party APIs), say so — don't guess.

**The code is law. The code never lies.**

## Anti-patterns

- "Yes, valid issue" without opening cited files
- "Closure makes it lazy" without checking framework semantics
- "All controllers do X" without grepping
- Paraphrasing an ADR/doc instead of checking code
- Answering "how does this work?" from path/class name alone
