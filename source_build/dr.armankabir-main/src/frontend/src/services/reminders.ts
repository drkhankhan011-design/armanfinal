import { del, get, post } from '../lib/apiClient';

export interface Reminder {
  id: number;
  patientId: number;
  drugName: string;
  times: string[];
  enabled: boolean;
  dose?: string | null;
  status?: string | null;
  createdAt?: string;
}

/** Reminders are patient business data and are persisted only by PHP/MySQL. */
export const reminderService = {
  async getByPatient(patientId: number): Promise<Reminder[]> {
    const result = await get<{ items?: Reminder[] } | Reminder[]>('/reminders/list.php', {
      patient_id: patientId,
    });
    return Array.isArray(result) ? result : result.items ?? [];
  },
  async create(data: Omit<Reminder, 'id'>): Promise<Reminder> {
    return post<Reminder>('/reminders/create.php', data);
  },
  async update(data: Partial<Reminder> & Pick<Reminder, 'id' | 'patientId'>): Promise<void> {
    await post('/reminders/update.php', data);
  },
  async remove(id: number): Promise<void> {
    await del('/reminders/delete.php', { id });
  },
};
