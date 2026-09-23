/**
 * Removed legacy hybrid-storage compatibility layer.
 *
 * Business data must use the domain services in `src/services`, which call the
 * PHP API and MySQL. Sync queues, canister migration, browser snapshots, and
 * client-side conflict state are not part of the server-owned architecture.
 */
