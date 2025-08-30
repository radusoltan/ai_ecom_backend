# API Documentation

## Response Envelope

All REST responses are wrapped in a standard envelope:

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

### Validation error

```json
{
  "status": "error",
  "data": null,
  "meta": { "timestamp": "2024-01-01T00:00:00+00:00", "request_id": "uuid", "tenant_id": "tenant" },
  "errors": [ { "code": "123", "message": "must not be blank", "field": "name" } ]
}
```

### Server error

```json
{
  "status": "error",
  "data": null,
  "meta": { "timestamp": "2024-01-01T00:00:00+00:00", "request_id": "uuid", "tenant_id": "tenant" },
  "errors": [ { "code": "500", "message": "Internal Server Error" } ]
}
```

### Paginated list

```json
{
  "status": "success",
  "data": [ {"id": 1}, {"id": 2} ],
  "meta": {
    "timestamp": "2024-01-01T00:00:00+00:00",
    "request_id": "uuid",
    "tenant_id": "tenant",
    "pagination": { "cursor": "abc", "has_more": true, "total": 2 }
  },
  "errors": []
}
```

## Rate limiting & CORS

Environment knobs:

- `API_IP_LIMIT`, `API_IP_INTERVAL`
- `API_KEY_LIMIT`, `API_KEY_RATE_INTERVAL`, `API_KEY_RATE_AMOUNT`
- `FRONTEND_ORIGINS` (comma-separated origins)

When limits are exceeded the API responds with `429` and headers:

```
X-RateLimit-Limit: 300
X-RateLimit-Remaining: 0
Retry-After: 60
```

Example body:

```json
{
  "status": "error",
  "data": null,
  "meta": { "timestamp": "2024-01-01T00:00:00+00:00", "request_id": "uuid", "tenant_id": null },
  "errors": [ { "code": 429, "message": "Too Many Requests" } ]
}
```
