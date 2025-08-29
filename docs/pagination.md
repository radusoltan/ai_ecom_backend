# Pagination

The API supports cursor-based pagination by default. Requests may include the following query parameters:

- `limit` (int, default 50, max 200)
- `cursor` (opaque string)
- `include_total` (bool)
- `page` and `per_page` (fallback page/limit mode)

Responses contain a `meta.pagination` object:

```json
{
  "cursor": "<opaque>",
  "has_more": true,
  "limit": 50,
  "total": null
}
```

The `cursor` encodes the position of the last item and can be passed to fetch the next page. `has_more` indicates if more results are available. The `total` field is `null` unless `include_total=true` is requested.
