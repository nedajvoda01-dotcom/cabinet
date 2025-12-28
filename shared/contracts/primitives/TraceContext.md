# TraceContext

Kind: **object**

Trace identifiers propagated across calls.

## Fields
- `requestId` (string, required) — Stable request correlation identifier.
  - constraint: non-empty
  - constraint: ascii
  - constraint: max_length:128
- `timestamp` (string, optional) — Optional ISO8601 timestamp of when the context was created.
  - constraint: iso8601
  - constraint: utc

## Example Payload
```json
{
    "requestId": "req-123456",
    "timestamp": "2024-01-01T00:00:00Z"
}
```
