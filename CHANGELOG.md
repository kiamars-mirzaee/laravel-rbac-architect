# Changelog

All notable changes to `laravel-rbac-architect` will be documented in this file.

## [1.0.0] - 2026-02-11

### Added
- Initial release
- Polymorphic context-based permissions
- Temporal permission support (activation and expiration dates)
- Root mode for superusers
- Many-to-many relationships for users, roles, and permissions
- Middleware for route protection
- Comprehensive documentation
- Unit tests

### Features
- `Role` and `Permission` models
- `HasRbac` trait for User model
- `ProtectByPermission` middleware
- Database migrations for RBAC tables
- Service provider for auto-discovery

## [Unreleased]

### Planned
- Permission caching for better performance
- Admin dashboard for managing roles and permissions
- Audit log for permission changes
- Role hierarchy support
- Permission groups
