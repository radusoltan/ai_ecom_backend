# Logging & Request Correlation

Every request is assigned a `request_id` that is echoed back to clients and attached to each log line. Clients may send their own `X-Request-ID` header; otherwise a UUIDv7 is generated.

## Channels

Logs are split across channels for easier filtering:
- `app` (default)
- `api`
- `security`
- `doctrine`
- `messenger`
- `http_client`
- `event`

All channels write newline-delimited JSON to stdout/stderr with ISO8601 timestamps.

## Tailing locally

```
# follow application logs
symfony server:log
```

## Kibana fields

The `request_id`, `tenant_id`, `user_id` and HTTP method/path/ip are indexed for searching and correlation.
