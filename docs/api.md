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
