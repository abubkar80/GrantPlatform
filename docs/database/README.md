# Database design

HCM_Grant_Platform.dbml contains 51 tables and 105 explicit relationships, converted from MySQL Schema and ERD v0.1. It was rendered in dbdiagram with editor warnings resolved.

This is a design artifact, not executed SQL or Laravel migrations. Notes describe rules that require application transactions and tests; an SQL export alone does not implement them.

Use installed Laravel and Spatie migrations as authoritative for framework-owned tables. Preserve unsigned foreign-key types and referenced keys. Implement range/status CHECK constraints, immutable history and cross-record consistency checks during migration/service development.

Main domains: identity, institutional reference data, permissions, applications, scoring, funds/rules, academics, awards, finance, evidence and operations.

audit_events stores activity history; ledger_events stores financial effects. Keep both append-only for runtime users. Polymorphic links are documented rather than falsely represented as physical user foreign keys.
