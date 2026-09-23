/**
 * Server-backed preference boundary.
 *
 * Only harmless UI preferences are allowed here. Patient records,
 * appointments, reminders, audit data, approvals, and clinical/business
 * records must use their PHP service modules instead.
 */
import { get, post } from './apiClient';

const preferenceKeyPattern = /^(patient_language|sidebar_collapsed|theme|ui_.*)$/;

function assertPreferenceKey(key: string): void {
  if (!preferenceKeyPattern.test(key)) {
    throw new Error(`Unsupported client preference: ${key}`);
  }
}

export const storage = {
  async getItem(key: string): Promise<string | null> {
    assertPreferenceKey(key);
    try {
      return (await get<{ value?: string | null }>('/preferences/get.php', { key }))?.value ?? null;
    } catch {
      return null;
    }
  },
  async setItem(key: string, value: string): Promise<void> {
    assertPreferenceKey(key);
    await post('/preferences/set.php', { key, value });
  },
  async removeItem(key: string): Promise<void> {
    assertPreferenceKey(key);
    await post('/preferences/remove.php', { key });
  },
  async clear(): Promise<void> {
    await post('/preferences/clear.php', {});
  },
  async length(): Promise<number> {
    return (await get<{ count?: number }>('/preferences/count.php'))?.count ?? 0;
  },
  async key(index: number): Promise<string | null> {
    return (await get<{ key?: string | null }>('/preferences/key.php', { index }))?.key ?? null;
  },
};

export async function getJson<T>(key: string): Promise<T | null> {
  const raw = await storage.getItem(key);
  if (raw === null) return null;
  try { return JSON.parse(raw) as T; } catch { return null; }
}

export function setJson(key: string, data: unknown): Promise<void> {
  return storage.setItem(key, JSON.stringify(data));
}

export async function getItemOr<T>(key: string, defaultValue: T): Promise<T> {
  return (await getJson<T>(key)) ?? defaultValue;
}

export function setItemJson(key: string, data: unknown): Promise<void> { return setJson(key, data); }
export function removeItemKey(key: string): Promise<void> { return storage.removeItem(key); }
