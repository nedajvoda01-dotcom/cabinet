# `_legacy` (backend)

This directory is the quarantine for code that violates the conveyor architecture described in `AGENT.md` and `AGENT1.md`.

- New features and modules **must not** be implemented here.
- Only relocations of existing violations are allowed, and they must be explicitly marked as legacy when moved.
- Active platform code **must not** import anything from `_legacy`; adapter/service usage from this path is forbidden.

Use `_legacy` to isolate historical or non-conforming code until it is rewritten to meet the normalized contracts and conveyor boundaries.
