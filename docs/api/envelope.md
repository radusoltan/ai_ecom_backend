# API Envelope

Both REST and GraphQL responses use a unified envelope structure.

```json
{
  "status": "success",
  "data": {},
  "meta": {
    "timestamp": "2024-01-01T00:00:00+00:00",
    "request_id": "uuid",
    "tenant_id": "tenant",
    "pagination": {
      "cursor": "abc",
      "has_more": true,
      "total": 100
    }
  },
  "errors": []
}
```

GraphQL responses expose the `meta` object under the `extensions` field:

```json
{
  "data": { ... },
  "extensions": {
    "meta": { ... }
  }
}
```
