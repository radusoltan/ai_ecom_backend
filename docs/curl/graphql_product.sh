#!/usr/bin/env bash
curl -H "Content-Type: application/json" -H "Authorization: Bearer <JWT>" -d '{"query":"{ product(id:\"1\"){ id name }}"}' https://api.localhost/graphql
