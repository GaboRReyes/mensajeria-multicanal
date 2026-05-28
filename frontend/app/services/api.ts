const API_BASE = "http://127.0.0.1:8000/api/v1";

function getToken(): string | null {
  return typeof window !== "undefined" ? localStorage.getItem("token") : null;
}

function authHeaders(contentType = "application/json"): Record<string, string> {
  const token = getToken();
  const headers: Record<string, string> = { Accept: "application/json" };
  if (contentType) headers["Content-Type"] = contentType;
  if (token) headers.Authorization = `Bearer ${token}`;
  return headers;
}

/* ─────────────────────────────────────────────
   Types
───────────────────────────────────────────── */

export interface Message {
  id: string;
  channel: string;
  recipient_masked: string;
  status: string;
  attempts: number;
  scheduled_at: string | null;
  sent_at: string | null;
  delivered_at: string | null;
  read_at: string | null;
  created_at: string;
}

export interface Template {
  id: number;
  name: string;
  /** 'email' | 'whatsapp' */
  channel: string;
  subject?: string | null;
  /** Cuerpo del mensaje — columna `body` en la BD */
  body: string;
}

export interface KpisResponse {
  total_messages: number;
  pending_messages: number;
  status_counts: Record<string, number>;
  channel_counts: Record<string, number>;
  active_templates: number;
  pending_list: Message[];
  updated_at: string;
}

/* ─────────────────────────────────────────────
   Messages
───────────────────────────────────────────── */

export interface SendMessagePayload {
  /** Destinatario: e-mail o número de teléfono */
  to: string;
  channel: "email" | "whatsapp";
  template_id?: number | null;
  /** Pares clave-valor que el template usa para renderizar */
  variables?: Record<string, string>;
  idempotency_key?: string;
  scheduled_at?: string | null;
}

export async function sendMessage(data: SendMessagePayload): Promise<Message> {
  const response = await fetch(`${API_BASE}/messages`, {
    method: "POST",
    headers: authHeaders(),
    body: JSON.stringify(data),
  });
  const result = await response.json();
  if (!response.ok) {
    throw new Error(result.error ?? result.message ?? "No se pudo enviar el mensaje.");
  }
  return result;
}

/** Envía por ambos canales (email + whatsapp) con el mismo destinatario. */
export async function sendMessageBoth(
  data: Omit<SendMessagePayload, "channel">
): Promise<[Message, Message]> {
  const [emailRes, waRes] = await Promise.all([
    sendMessage({ ...data, channel: "email" }),
    sendMessage({ ...data, channel: "whatsapp" }),
  ]);
  return [emailRes, waRes];
}

export async function getMessages(): Promise<Message[]> {
  const response = await fetch(`${API_BASE}/messages`, {
    headers: authHeaders(),
  });
  if (!response.ok) throw new Error("No se pudieron cargar los mensajes.");
  return response.json();
}

/** Cancela un mensaje programado o en cola (cambia status a 'cancelado'). */
export async function cancelMessage(uuid: string): Promise<Message> {
  const response = await fetch(`${API_BASE}/messages/${uuid}`, {
    method: "DELETE",
    headers: authHeaders(),
  });
  const result = await response.json();
  if (!response.ok) {
    throw new Error(result.error ?? result.message ?? "No se pudo cancelar el mensaje.");
  }
  return result;
}

/* ─────────────────────────────────────────────
   Templates
───────────────────────────────────────────── */

export async function getTemplates(): Promise<Template[]> {
  const response = await fetch(`${API_BASE}/templates`, {
    headers: authHeaders(),
  });
  const result = await response.json();
  if (!response.ok) throw new Error(result.message ?? "Error al cargar las plantillas.");
  return result;
}

export async function createTemplate(
  data: Omit<Template, "id">
): Promise<Template> {
  const response = await fetch(`${API_BASE}/templates`, {
    method: "POST",
    headers: authHeaders(),
    body: JSON.stringify(data),
  });
  const result = await response.json();
  if (!response.ok) throw new Error(result.message ?? "Error al crear la plantilla.");
  return result;
}

export async function updateTemplate(
  id: number,
  data: Partial<Omit<Template, "id">>
): Promise<Template> {
  const response = await fetch(`${API_BASE}/templates/${id}`, {
    method: "PUT",
    headers: authHeaders(),
    body: JSON.stringify(data),
  });
  const result = await response.json();
  if (!response.ok) throw new Error(result.message ?? "Error al actualizar la plantilla.");
  return result;
}

export async function deleteTemplate(id: number): Promise<void> {
  const response = await fetch(`${API_BASE}/templates/${id}`, {
    method: "DELETE",
    headers: authHeaders(),
  });
  if (!response.ok) {
    const result = await response.json().catch(() => null);
    throw new Error(result?.message ?? "Error al eliminar la plantilla.");
  }
}

/* ─────────────────────────────────────────────
   Reports
───────────────────────────────────────────── */

export async function getReportsKpis(): Promise<KpisResponse> {
  const response = await fetch(`${API_BASE}/reports/kpis`, {
    headers: authHeaders(),
  });
  const result = await response.json();
  if (!response.ok) throw new Error(result.message ?? "Error al cargar los KPIs.");
  return result;
}

export async function exportReport(format: "pdf" | "excel"): Promise<void> {
  const response = await fetch(`${API_BASE}/reports/export/${format}`, {
    headers: authHeaders(""),
  });

  if (!response.ok) {
    const result = await response.json().catch(() => null);
    throw new Error(result?.message ?? "Error en la exportación.");
  }

  const blob = await response.blob();
  const contentType =
    response.headers.get("content-type") ?? "application/octet-stream";
  const extension = format === "pdf" ? "pdf" : "xlsx";
  const url = URL.createObjectURL(new Blob([blob], { type: contentType }));
  const link = document.createElement("a");
  link.href = url;
  link.download = `reportes-kpis.${extension}`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}

/* ─────────────────────────────────────────────
   Admin
───────────────────────────────────────────── */

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'client' | 'developer';
  is_active: boolean;
  monthly_limit: number | null;
  used_this_month: number;
  messages_count?: number;
  campaigns_count?: number;
  contacts_count?: number;
  created_at: string;
}

export interface AdminStats {
  users: {
    total: number;
    active: number;
    by_role: Record<string, number>;
  };
  messages: {
    total: number;
    by_status: Record<string, number>;
    by_channel: Record<string, number>;
  };
  campaigns: {
    total: number;
    by_status: Record<string, number>;
  };
  updated_at: string;
}

export interface PaginatedUsers {
  data: AdminUser[];
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

export async function getAdminStats(): Promise<AdminStats> {
  const r = await fetch(`${API_BASE}/admin/stats`, { headers: authHeaders() });
  const result = await r.json();
  if (!r.ok) throw new Error(result.message ?? "Error al cargar estadísticas.");
  return result;
}

export async function getAdminUsers(params?: { role?: string; search?: string; page?: number }): Promise<PaginatedUsers> {
  const qs = new URLSearchParams();
  if (params?.role) qs.set("role", params.role);
  if (params?.search) qs.set("search", params.search);
  if (params?.page) qs.set("page", String(params.page));
  const r = await fetch(`${API_BASE}/admin/users?${qs}`, { headers: authHeaders() });
  const result = await r.json();
  if (!r.ok) throw new Error(result.message ?? "Error al cargar usuarios.");
  return result;
}

export interface CreateUserPayload {
  name: string;
  email: string;
  password: string;
  role: 'admin' | 'client' | 'developer';
  monthly_limit?: number | null;
}

export async function createAdminUser(data: CreateUserPayload): Promise<AdminUser> {
  const r = await fetch(`${API_BASE}/admin/users`, {
    method: "POST",
    headers: authHeaders(),
    body: JSON.stringify(data),
  });
  const result = await r.json();
  if (!r.ok) throw new Error(result.message ?? "Error al crear usuario.");
  return result;
}

export async function updateAdminUser(id: number, data: Partial<CreateUserPayload & { is_active: boolean }>): Promise<AdminUser> {
  const r = await fetch(`${API_BASE}/admin/users/${id}`, {
    method: "PUT",
    headers: authHeaders(),
    body: JSON.stringify(data),
  });
  const result = await r.json();
  if (!r.ok) throw new Error(result.message ?? "Error al actualizar usuario.");
  return result;
}

export async function deleteAdminUser(id: number): Promise<void> {
  const r = await fetch(`${API_BASE}/admin/users/${id}`, {
    method: "DELETE",
    headers: authHeaders(),
  });
  if (!r.ok) {
    const result = await r.json().catch(() => null);
    throw new Error(result?.error ?? result?.message ?? "Error al eliminar usuario.");
  }
}

export async function toggleAdminUser(id: number): Promise<{ is_active: boolean }> {
  const r = await fetch(`${API_BASE}/admin/users/${id}/toggle`, {
    method: "PATCH",
    headers: authHeaders(),
  });
  const result = await r.json();
  if (!r.ok) throw new Error(result.message ?? "Error al cambiar estado.");
  return result;
}

/* ─────────────────────────────────────────────
   Dev — API Keys
───────────────────────────────────────────── */

export interface ApiKey {
  id: number;
  name: string;
  prefix: string;
  abilities: string[];
  is_active: boolean;
  last_used_at: string | null;
  expires_at: string | null;
  created_at: string;
}

export async function getDevApiKeys(): Promise<ApiKey[]> {
  const r = await fetch(`${API_BASE}/dev/api-keys`, { headers: authHeaders() });
  const result = await r.json();
  if (!r.ok) throw new Error(result.message ?? "Error al cargar API keys.");
  return result;
}

export interface CreateApiKeyPayload {
  name: string;
  abilities?: string[];
  env?: 'live' | 'test';
  expires_at?: string | null;
}

export interface CreatedApiKey {
  api_key: ApiKey;
  token: string;
  warning: string;
}

export async function createDevApiKey(data: CreateApiKeyPayload): Promise<CreatedApiKey> {
  const r = await fetch(`${API_BASE}/dev/api-keys`, {
    method: "POST",
    headers: authHeaders(),
    body: JSON.stringify(data),
  });
  const result = await r.json();
  if (!r.ok) throw new Error(result.error ?? result.message ?? "Error al crear API key.");
  return result;
}

export async function deleteDevApiKey(id: number): Promise<void> {
  const r = await fetch(`${API_BASE}/dev/api-keys/${id}`, {
    method: "DELETE",
    headers: authHeaders(),
  });
  if (!r.ok) {
    const result = await r.json().catch(() => null);
    throw new Error(result?.message ?? "Error al eliminar API key.");
  }
}

export async function revokeDevApiKey(id: number): Promise<void> {
  const r = await fetch(`${API_BASE}/dev/api-keys/${id}/revoke`, {
    method: "PATCH",
    headers: authHeaders(),
  });
  if (!r.ok) {
    const result = await r.json().catch(() => null);
    throw new Error(result?.message ?? "Error al revocar API key.");
  }
}
