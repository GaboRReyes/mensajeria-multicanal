const API_BASE = "http://127.0.0.1:8000/api/v1";

function getToken(): string | null {
  return typeof window !== "undefined" ? localStorage.getItem("token") : null;
}

function authHeaders(contentType = "application/json") {
  const token = getToken();
  const headers: Record<string, string> = {
    Accept: "application/json",
  };

  if (contentType) {
    headers["Content-Type"] = contentType;
  }

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  return headers;
}

export interface SendMessagePayload {
  recipient: string;
  content: string;
  channel: "whatsapp" | "email" | "both";
}

export async function sendMessage(data: SendMessagePayload) {
  const response = await fetch(`${API_BASE}/messages`, {
    method: "POST",
    headers: authHeaders(),
    body: JSON.stringify(data),
  });

  const result = await response.json();
  if (!response.ok) {
    throw new Error(result.message || "No se pudo enviar el mensaje.");
  }

  return result;
}

export async function getReportsKpis() {
  const response = await fetch(`${API_BASE}/reports/kpis`, {
    headers: authHeaders(),
  });

  const result = await response.json();
  if (!response.ok) {
    throw new Error(result.message || "Error al cargar los KPIs.");
  }

  return result;
}

export async function exportReport(format: "pdf" | "excel") {
  const response = await fetch(`${API_BASE}/reports/export/${format}`, {
    headers: authHeaders(""),
  });

  if (!response.ok) {
    const result = await response.json().catch(() => null);
    throw new Error(result?.message || "Error en la exportación.");
  }

  const blob = await response.blob();
  const contentType = response.headers.get("content-type") || "application/octet-stream";
  const extension = format === "pdf" ? "pdf" : "xlsx";
  const filename = `reportes-kpis.${extension}`;
  const url = URL.createObjectURL(new Blob([blob], { type: contentType }));
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}
