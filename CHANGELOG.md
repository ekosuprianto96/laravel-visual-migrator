# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [2.4.1] - 2026-02-19

### Added
- **Draft Persistence** Feature: New tables that haven't been saved to migrations are preserved in the browser (LocalStorage) on refresh.
- **Smart Merge Logic**: Intelligent merging between remote backend data and local draft data on the frontend.
- `isPersisted` flag on collections to distinguish between draft tables and officially saved tables.
- Fixed automatic schema synchronization after successfully saving migrations.

### Fixed
- **Reset Node Position** Fix: Visual table positions are no longer reset when the synchronization process ("Start Sync") is performed.
- **Toolbar Visual Bug** Fix: Removed the "jitter" effect or overflow to the right when the toolbar is closed in the Dashboard.
- Optimized CSS transitions on the toolbar for a smoother experience.

## [2.4.0] - 2026-02-18

### Added
- Initialization of **Laravel Adapter Package**.
- Implementation of `MigrationGenerator.php` to transform visual schema into Laravel PHP migration files.
- Implementation of `MigrationParser.php` to visualize existing Laravel migrations.
- **Agnostic API Bridge** integration for UI to Backend communication.
- **Debounce & Throttling** feature on API requests for network efficiency.
- Auto-save layout metadata to Laravel backend.
- Table selection feature in the code export modal.

### Changed
- Refactored `MigrationGenerator` into an independent `saveLayoutMeta` method.
- Auto-Arrange layout improvements with column wrapping constraints.

## [2.3.0] - 2026-02-10

### Added
- Support for **Nested Fields** in the diagram.
- Support for cardinality relations (1:1, 1:N, N:N).
- Undo/Redo implementation in state management.
