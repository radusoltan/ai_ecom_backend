# GraphQL Resolvers

Resolvers transform Doctrine entities into read models for GraphQL. To add a new resolver:

1. Create a view DTO and factory under `src/Shared/View`.
2. Implement a resolver class under `src/Infrastructure/API/GraphQL/Resolver` and register it with
   `#[AsGraphQlResolver(entity: YourEntity::class, operation: 'item_query')]`.
3. Type the resolver signature as `__invoke(YourEntity $entity, Context $context): YourView` and
   delegate mapping to the factory.
4. Inject `Context` to access the current `TenantId` and pass it down for tenant-scoped queries.

The resolver should orchestrate only; heavy lifting (batch loading, hydration, error handling) lives in
factories or dedicated services. See `ProductResolver` for a reference implementation.
