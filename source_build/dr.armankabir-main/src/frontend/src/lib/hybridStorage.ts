/**
 * Server-backed compatibility layer.
 *
 * Business data is owned by the PHP/MySQL services. This module intentionally
 * contains no browser persistence, migration, or offline bootstrap behavior.
 */

import { get, post } from './apiClient';

export type SyncQueueItemType =
  | 'upsertPatient'
  | 'upsertVisit'
  | 'upsertPrescription'
  | 'upsertAppointment'
  | 'upsertQueueEntry'
  | 'upsertObservation'
  | 'upsertBed'
  | 'upsertDailyProgressNote'
  | 'upsertHandover'
  | 'upsertMedicationAdministration'
  | 'upsertFrontPageContent';

export interface SyncQueueItem {
  id: string;
  timestamp: number;
  type?: SyncQueueItemType;
  operation?: 'create' | 'update' | 'delete';
  entityType?: string;
  entityId?: string;
  data: unknown;
  retryCount: number;
}

export interface SyncStatus {
  isOnline: boolean;
  pendingChanges: number;
  lastSyncAt?: Date;
}

export interface SyncConflict {
  entityType: string;
  entityId: string;
  localVersion: unknown;
  serverVersion: unknown;
  localUpdatedAt?: number;
  serverUpdatedAt?: number;
  detectedAt: number;
}

export interface MigrationProgress {
  total: number;
  migrated: number;
  message: string;
}

// Compatibility exports retained for older components. There is no client
// queue: every business-data write must go through a domain service/API.
export function enqueueSync(_item: SyncQueueItem): void {}
export function removeFromQueue(_type: string, _ids: Set<string>): void {}
export function loadSyncQueue(): SyncQueueItem[] { return []; }
export function getConflicts(): SyncConflict[] { return []; }
export function getConflictsCount(): number { return 0; }
export function addConflict(_conflict: SyncConflict): void {}
export function resolveConflict(_entityId: string, _choice: 'mine' | 'theirs'): void {}
export function isMigrationDone(): boolean { return true; }
export function markMigrationDone(): void {}
export function getDeviceId(): string { return 'php-session'; }
export function setLastSyncTs(_ts: number): void {}
export function getPendingChangesCount(): number { return 0; }

export async function saveFrontPageContentWithSync(): Promise<void> {}

export async function loadFrontPageContentFromServer(): Promise<Record<string, unknown> | null> {
  try { return await get<Record<string, unknown>>('/frontpage/get.php'); }
  catch { return null; }
}

export async function saveFrontPageContentToServer(data: Record<string, unknown>): Promise<boolean> {
  try { await post('/frontpage/save.php', data); return true; }
  catch { return false; }
}

// These names remain only so legacy imports fail closed instead of creating a
// client-side business-data store. Use the corresponding service module.
export function getClinicalStore(): Record<string, unknown[]> { return {}; }
export function getClinicalEntities(_entityType: string): unknown[] { return []; }
export function saveClinicalEntities(_entityType: string, _items: unknown[]): void {}
export function saveClinicalEntitiesWithSync(_entityType: string, _items: unknown[]): void {}
export function nextClinicalId(_items: { id?: unknown }[]): number { return 1; }
export async function flushContentQueue(): Promise<void> {}
