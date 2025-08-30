# 0001-rbac-abac-starter

## Context
We need foundational role and attribute based access control with multi-tenant awareness. Policies must be centrally managed and evaluated consistently across REST and GraphQL endpoints.

## Decision
- Policies are declared in `config/packages/security_policies.yaml` and loaded into a cacheable `PolicyEvaluator` that uses Symfony ExpressionLanguage.
- Expressions expose `is_granted()`, `user`, `subject`, `tenant()` and `feature(name)` helpers.
- Custom voters invoke `PolicyEvaluator` and short-circuit when tenant context does not match the resource.
- API Platform operations delegate to these voters via `security` expressions.

## Consequences
- Adding a new policy requires updating the YAML file, wiring a voter, and referencing it via `is_granted('policy.key', object)` on resources or operations.
- `tenant()` derives the current tenant from `TenantContext`; `feature(name)` checks feature flags through `FeatureFlagService`.
- The pattern scales to future helpers (e.g. `segment()`, `timeOfDay()`) and more policies.
