# Pipeline Orchestration Engine

## Purpose

This directory contains the **pipeline execution engine** of Cabinet.

The pipeline is responsible for:
- asynchronous task execution
- stage-based processing
- retries and backoff
- idempotency
- locking
- failure classification

The pipeline does NOT:
- contain business logic
- interpret data semantics
- make domain decisions

## Core Concepts

- Stage — explicit execution step
- Job — queued execution unit
- Worker — stage executor
- Driver — stage-specific adapter
- RetryPolicy — deterministic retry rules
- Lock — concurrency guard
- Idempotency — duplicate execution protection

## Execution Model

1. Task enters pipeline
2. Stage job is enqueued
3. Worker picks job
4. Driver executes stage
5. Result is recorded
6. Next stage is scheduled or pipeline ends

## Failure Handling

Failures are:
- classified
- retried OR degraded OR failed
- never silently ignored

## Final Statement

The pipeline is deterministic by design.
If execution becomes implicit — the design is broken.
