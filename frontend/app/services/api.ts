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
