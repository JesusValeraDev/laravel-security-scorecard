---
description: Run the full implementation cycle using the repo bootstrap, Spec Kit artifacts, approval gates, adversarial review, remediation, tests, and delivery handoff.
argument-hint: "<JIRA-KEY-or-issue-URL-or-freeform-request>"
allowed-tools: Read, Grep, Glob, Bash, Edit, MultiEdit, Write
disable-model-invocation: true
---

Repo-local entrypoint for requests like `implement JIRA-1234` or `implement retry-safe invoice sync`.

Required behavior:
1. Read the `implementation-cycle` skill file and execute it end-to-end for the current repo and `$ARGUMENTS`.
2. Treat that skill as the canonical source of truth for the implementation cycle — do not duplicate or improvise the workflow here.
