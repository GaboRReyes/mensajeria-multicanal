"use client";

import { useRouter } from "next/navigation";
import { FormEvent, useCallback, useEffect, useState } from "react";
import {
  LayoutDashboard,
  Mail,
  FileText,
  BarChart3,
  LogOut,
  Menu,
  X,
  Plus,
  Pencil,
  Trash2,
  RefreshCw,
} from "lucide-react";
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Cell,
} from "recharts";

import {
  exportReport,
  getReportsKpis,
  sendMessage,
  sendMessageBoth,
  getTemplates,
  createTemplate,
  updateTemplate,
  deleteTemplate,
  getMessages,
  cancelMessage,
} from "../../services/api";

import type { Template, KpisResponse, Message } from "../../services/api";
import styles from "./dashboard.module.css";

/* ─────────────────────────────────────────────
   Types / constants
───────────────────────────────────────────── */

type Section = "home" | "messages" | "history" | "templates" | "reports";

const STATUS_COLORS: Record<string, string> = {
  enviado: "#16a34a",
  entregado: "#2563eb",
  leido: "#7c3aed",
  fallido: "#dc2626",
  programado: "#d97706",
  encolado: "#6b7280",
  cancelado: "#9ca3af",
};

/* ─────────────────────────────────────────────
   Dashboard (shell)
───────────────────────────────────────────── */

export default function Dashboard() {
  const router = useRouter();
  const [user, setUser] = useState<{ name: string; email: string } | null>(null);
  const [loading, setLoading] = useState(true);
  const [activeSection, setActiveSection] = useState<Section>("home");
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [kpis, setKpis] = useState<KpisResponse | null>(null);

  /* KPIs (cargados una vez al montar) */
  useEffect(() => {
    getReportsKpis()
      .then(setKpis)
      .catch((err) => console.error("KPIs:", err));
  }, []);

  /* Auth guard */
  useEffect(() => {
    const token = localStorage.getItem("token");
    const userData = localStorage.getItem("user");
    if (!token) { router.push("/pages/login"); return; }
    const parsed = userData ? JSON.parse(userData) : null;
    if (parsed?.role === "admin") { router.replace("/pages/admin"); return; }
    if (parsed?.role === "developer") { router.replace("/pages/dev"); return; }
    if (parsed) setUser(parsed);
    setLoading(false);
  }, [router]);

  const handleLogout = () => {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    router.push("/pages/login");
  };

  const menuItems: { id: Section; label: string; icon: React.ElementType }[] = [
    { id: "home",      label: "Inicio",    icon: LayoutDashboard },
    { id: "messages",  label: "Mensajes",  icon: Mail },
    { id: "history",   label: "Historial", icon: FileText },
    { id: "templates", label: "Plantillas",icon: FileText },
    { id: "reports",   label: "Reportes",  icon: BarChart3 },
  ];

  if (loading) return <div className={styles.loading}>Cargando…</div>;

  return (
    <div className={styles.layout}>
      {/* Overlay móvil */}
      <div
        className={`${styles.overlay} ${sidebarOpen ? styles.visible : ""}`}
        onClick={() => setSidebarOpen(false)}
      />

      {/* Sidebar */}
      <aside className={`${styles.sidebar} ${sidebarOpen ? styles.open : ""}`}>
        <div className={styles.sidebarHeader}>
          <span className={styles.sidebarTitle}>Panel</span>
          <button onClick={() => setSidebarOpen(false)} className={styles.menuButton} aria-label="Cerrar menú">
            <X size={18} />
          </button>
        </div>

        <nav className={styles.sidebarNav}>
          <div className={styles.sidebarMenu}>
            {menuItems.map((item) => (
              <button
                key={item.id}
                onClick={() => { setActiveSection(item.id); setSidebarOpen(false); }}
                className={`${styles.sidebarItem} ${activeSection === item.id ? styles.active : ""}`}
              >
                <item.icon size={17} />
                <span>{item.label}</span>
              </button>
            ))}
          </div>
        </nav>

        <div className={styles.sidebarFooter}>
          <button onClick={handleLogout} className={styles.logoutButton}>
            <LogOut size={17} />
            <span>Cerrar sesión</span>
          </button>
        </div>
      </aside>

      {/* Main */}
      <div className={styles.main}>
        <header className={styles.header}>
          <button onClick={() => setSidebarOpen(true)} className={styles.menuButton} aria-label="Abrir menú">
            <Menu size={20} />
          </button>
          <span className={styles.userPill}>{user?.name ?? "Usuario"}</span>
        </header>

        <main className={styles.content}>
          {activeSection === "home"      && <HomeSection      user={user} kpis={kpis} />}
          {activeSection === "messages"  && <MessagesSection  />}
          {activeSection === "history"   && <HistorySection   />}
          {activeSection === "templates" && <TemplatesSection />}
          {activeSection === "reports"   && <ReportsSection   />}
        </main>
      </div>
    </div>
  );
}

/* ─────────────────────────────────────────────
   HOME
───────────────────────────────────────────── */

function HomeSection({
  user,
  kpis,
}: {
  user: { name: string } | null;
  kpis: KpisResponse | null;
}) {
  return (
    <>
      <div className={styles.card}>
        <p className={styles.sectionLabel}>Bienvenido</p>
        <h2 className={styles.cardTitle}>Hola, {user?.name ?? "Usuario"}</h2>
        <p className={styles.cardDescription}>
          Desde este panel puedes gestionar mensajes, plantillas, ver reportes y
          configurar los ajustes de tu plataforma.
        </p>
      </div>

      <div className={styles.statsGrid}>
        <div className={styles.statCard}>
          <p className={styles.statLabel}>Total mensajes</p>
          <p className={styles.statValue}>{kpis?.total_messages ?? "—"}</p>
        </div>
        <div className={styles.statCard}>
          <p className={styles.statLabel}>Pendientes</p>
          <p className={styles.statValue}>{kpis?.pending_messages ?? "—"}</p>
        </div>
        <div className={styles.statCard}>
          <p className={styles.statLabel}>Plantillas activas</p>
          <p className={styles.statValue}>{kpis?.active_templates ?? "—"}</p>
        </div>
      </div>
    </>
  );
}

/* ─────────────────────────────────────────────
   MESSAGES
───────────────────────────────────────────── */

function MessagesSection() {
  const [to, setTo]             = useState("");
  const [content, setContent]   = useState("");
  const [subject, setSubject]   = useState("");
  const [channel, setChannel]   = useState<"whatsapp" | "email" | "both">("whatsapp");
  const [scheduledAt, setScheduledAt] = useState("");
  const [selectedTemplate, setSelectedTemplate] = useState<Template | null>(null);

  const [templates, setTemplates]           = useState<Template[]>([]);
  const [templatesOpen, setTemplatesOpen]   = useState(false);
  const [templatesError, setTemplatesError] = useState<string | null>(null);

  const [sending, setSending]   = useState(false);
  const [status, setStatus]     = useState<string | null>(null);
  const [error, setError]       = useState<string | null>(null);

  /* Cargar plantillas al abrir el dropdown */
  const openTemplates = async () => {
    if (!templatesOpen && templates.length === 0) {
      try {
        setTemplates(await getTemplates());
      } catch (err) {
        setTemplatesError(err instanceof Error ? err.message : "Error al cargar plantillas.");
      }
    }
    setTemplatesOpen((prev) => !prev);
  };

  const applyTemplate = (t: Template) => {
    setSelectedTemplate(t);
    setContent(t.body ?? "");
    setSubject(t.subject ?? "");
    if (t.channel === "whatsapp" || t.channel === "email") setChannel(t.channel);
    setTemplatesOpen(false);
  };

  const clearTemplate = () => {
    setSelectedTemplate(null);
    setContent("");
    setSubject("");
  };

  const handleSend = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setStatus(null);
    setError(null);
    setSending(true);
    // WhatsApp (solo o en 'ambos') sin plantilla = texto libre,
    // que solo funciona en ventana de 24h. Avisar al usuario.
    if ((channel === "whatsapp" || channel === "both") && !selectedTemplate) {
      const ok = window.confirm(
        "Estás enviando texto libre por WhatsApp. Esto solo funciona si el contacto te escribió en las últimas 24h. " +
        "Para iniciar conversación, selecciona una plantilla.\n\n¿Continuar de todos modos?"
      );
      if (!ok) { setSending(false); return; }
    }

    // Siempre se envía el texto del textarea como "text".
    // Para email también se incluye el asunto personalizado.
    const variables: Record<string, string> = { text: content };
    if ((channel === "email" || channel === "both") && subject.trim()) {
      variables.subject = subject.trim();
    }

    const payload = {
      to,
      template_id: selectedTemplate?.id ?? null,
      variables,
      scheduled_at: scheduledAt || null,
    };

    try {
      if (channel === "both") {
        await sendMessageBoth(payload);
        setStatus(scheduledAt ? "Mensajes programados por Email y WhatsApp." : "Enviado por Email y WhatsApp.");
      } else {
        const msg = await sendMessage({ ...payload, channel });
        setStatus(
          scheduledAt
            ? `Mensaje programado (${msg.status}).`
            : `Mensaje encolado por ${channel === "email" ? "Email" : "WhatsApp"}.`
        );
      }
      setTo(""); setContent(""); setSubject(""); setScheduledAt(""); setSelectedTemplate(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error al enviar el mensaje.");
    } finally {
      setSending(false);
    }
  };

  return (
    <div className={styles.card}>
      <p className={styles.sectionLabel}>Mensajes</p>
      <h2 className={styles.cardTitle}>Enviar mensaje</h2>
      <p className={styles.cardDescription}>
        Envía mensajes por WhatsApp, Email o ambos canales.
      </p>

      <div className={styles.formSection}>
        <div className={styles.formCard}>
          <div className={styles.formCardHeader}>
            <h3>Nuevo mensaje</h3>

            {/* Template dropdown */}
            <div className={styles.templateDropdownWrapper}>
              <button type="button" className={styles.templateToggle} onClick={openTemplates}>
                <FileText size={15} />
                {selectedTemplate ? selectedTemplate.name : "Usar plantilla"}
                <span className={`${styles.chevron} ${templatesOpen ? styles.chevronUp : ""}`}>▾</span>
              </button>

              {templatesOpen && (
                <div className={styles.templateDropdown}>
                  {templatesError && (
                    <p className={styles.errorText} style={{ padding: "0.6rem 0.9rem", margin: 0 }}>
                      {templatesError}
                    </p>
                  )}
                  {!templatesError && templates.length === 0 && (
                    <p className={styles.infoText} style={{ padding: "0.6rem 0.9rem", margin: 0 }}>
                      No hay plantillas disponibles.
                    </p>
                  )}
                  {templates.map((t) => (
                    <button
                      key={t.id}
                      type="button"
                      className={`${styles.templateOption} ${selectedTemplate?.id === t.id ? styles.templateOptionActive : ""}`}
                      onClick={() => applyTemplate(t)}
                    >
                      <span className={styles.templateOptionName}>{t.name}</span>
                      <span className={styles.templateOptionMeta}>
                        {t.channel}{t.subject ? ` · ${t.subject}` : ""}
                      </span>
                      {t.body ? (
                        <span className={styles.templateOptionBody}>
                          {t.body.length > 90 ? t.body.slice(0, 90) + "…" : t.body}
                        </span>
                      ) : (
                        <span className={styles.templateOptionBodyEmpty}>Sin contenido</span>
                      )}
                    </button>
                  ))}
                </div>
              )}
            </div>
          </div>

          <form onSubmit={handleSend} className={styles.inputGroup}>
            <input
              type="text"
              value={to}
              onChange={(e) => setTo(e.target.value)}
              placeholder="Destinatario (email o teléfono)"
              className={styles.input}
              required
            />

            {selectedTemplate && (
              <div className={styles.templateBanner}>
                <FileText size={14} />
                <span>
                  Plantilla: <strong>{selectedTemplate.name}</strong>
                  {selectedTemplate.subject && (
                    <span className={styles.templateBannerSubject}> — {selectedTemplate.subject}</span>
                  )}
                </span>
                <button
                  type="button"
                  className={styles.templateBannerClear}
                  onClick={clearTemplate}
                  title="Quitar plantilla"
                >
                  <X size={13} />
                </button>
              </div>
            )}

            {/* Asunto — solo visible cuando el canal incluye email */}
            {(channel === "email" || channel === "both") && (
              <input
                type="text"
                value={subject}
                onChange={(e) => setSubject(e.target.value)}
                placeholder={
                  selectedTemplate?.subject
                    ? `Asunto: ${selectedTemplate.subject}`
                    : "Asunto del correo (opcional)"
                }
                className={styles.input}
              />
            )}

            <textarea
              value={content}
              onChange={(e) => setContent(e.target.value)}
              placeholder="Contenido del mensaje — o selecciona una plantilla arriba"
              className={`${styles.textarea} ${selectedTemplate ? styles.textareaWithTemplate : ""}`}
              rows={5}
              required
            />

            {/* Canal */}
            <div className={styles.fieldGroup}>
              {(["whatsapp", "email", "both"] as const).map((ch) => (
                <label key={ch} className={styles.radioLabel}>
                  <input type="radio" value={ch} checked={channel === ch} onChange={() => setChannel(ch)} />
                  {ch === "whatsapp" ? "WhatsApp" : ch === "email" ? "Email" : "Ambos"}
                </label>
              ))}
            </div>

            {/* Programación */}
            <div className={styles.scheduleGroup}>
              <label className={styles.scheduleLabel}>Programar envío (opcional)</label>
              <input
                type="datetime-local"
                value={scheduledAt}
                onChange={(e) => setScheduledAt(e.target.value)}
                className={styles.input}
                min={new Date(Date.now() - new Date().getTimezoneOffset() * 60000)
                  .toISOString()
                  .slice(0, 16)}
              />
            </div>

            {status && <p className={styles.successText}>{status}</p>}
            {error  && <p className={styles.errorText}>{error}</p>}

            <button type="submit" className={styles.primaryButton} disabled={sending}>
              {sending ? "Procesando…" : scheduledAt ? "Programar mensaje" : "Enviar"}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}

/* ─────────────────────────────────────────────
   HISTORY
───────────────────────────────────────────── */

function HistorySection() {
  const [messages, setMessages]       = useState<Message[]>([]);
  const [loading, setLoading]         = useState(true);
  const [error, setError]             = useState<string | null>(null);
  const [processingId, setProcessingId] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setMessages(await getMessages());
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error al cargar mensajes.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);

  const handleCancel = async (id: string) => {
    if (!window.confirm("¿Cancelar este mensaje?")) return;
    try {
      setProcessingId(id);
      await cancelMessage(id);
      setMessages((prev) =>
        prev.map((m) => (m.id === id ? { ...m, status: "cancelado" } : m))
      );
    } catch (err) {
      alert(err instanceof Error ? err.message : "Error al cancelar.");
    } finally {
      setProcessingId(null);
    }
  };

  /** Eliminar solo del listado local (no existe endpoint DELETE real). */
  const handleRemove = async (id: string) => {
    if (!window.confirm("¿Quitar este mensaje del historial local?")) return;
    setMessages((prev) => prev.filter((m) => m.id !== id));
  };

  return (
    <div className={styles.card}>
      <div className={styles.sectionHeaderRow}>
        <div>
          <p className={styles.sectionLabel}>Historial</p>
          <h2 className={styles.cardTitle}>Historial de mensajes</h2>
          <p className={styles.cardDescription}>
            Últimos 50 mensajes enviados, programados y cancelados.
          </p>
        </div>
        <button className={styles.ghostButton} onClick={load} disabled={loading}>
          <RefreshCw size={14} style={{ display: "inline", marginRight: 4 }} />
          Actualizar
        </button>
      </div>

      {loading && <p>Cargando mensajes…</p>}
      {error   && <p className={styles.errorText}>{error}</p>}
      {!loading && messages.length === 0 && !error && <p>No hay mensajes registrados.</p>}

      {!loading && messages.length > 0 && (
        <div className={styles.tableWrapper}>
          <table className={styles.messagesTable}>
            <thead>
              <tr>
                <th>Canal</th>
                <th>Destinatario</th>
                <th>Estado</th>
                <th>Intentos</th>
                <th>Programado</th>
                <th>Enviado</th>
                <th>Entregado</th>
                <th>Leído</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {messages.map((msg) => (
                <tr key={msg.id}>
                  <td>
                    <span className={`${styles.channelTag} ${msg.channel === "email" ? styles.channelEmail : styles.channelWhatsapp}`}>
                      {msg.channel}
                    </span>
                  </td>
                  <td>{msg.recipient_masked}</td>
                  <td>
                    <span className={`${styles.statusBadge} ${styles[msg.status] ?? ""}`}>
                      {msg.status}
                    </span>
                  </td>
                  <td>{msg.attempts}</td>
                  <td>{msg.scheduled_at ? new Date(msg.scheduled_at).toLocaleString() : "—"}</td>
                  <td>{msg.sent_at      ? new Date(msg.sent_at).toLocaleString()      : "—"}</td>
                  <td>{msg.delivered_at ? new Date(msg.delivered_at).toLocaleString() : "—"}</td>
                  <td>{msg.read_at      ? new Date(msg.read_at).toLocaleString()      : "—"}</td>
                  <td>
                    <div className={styles.actionsGroup}>
                      {(msg.status === "programado" || msg.status === "encolado") && (
                        <button
                          className={styles.cancelButton}
                          onClick={() => handleCancel(msg.id)}
                          disabled={processingId === msg.id}
                        >
                          Cancelar
                        </button>
                      )}
                      <button
                        className={styles.deleteButton}
                        onClick={() => handleRemove(msg.id)}
                        disabled={processingId === msg.id}
                      >
                        Quitar
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

/* ─────────────────────────────────────────────
   TEMPLATES  (CRUD completo)
───────────────────────────────────────────── */

type TemplateForm = { name: string; subject: string; body: string; channel: string };
const EMPTY_FORM: TemplateForm = { name: "", subject: "", body: "", channel: "email" };

function TemplatesSection() {
  const [templates, setTemplates]   = useState<Template[] | null>(null);
  const [error, setError]           = useState<string | null>(null);
  const [saving, setSaving]         = useState(false);

  /* Create form */
  const [showCreate, setShowCreate] = useState(false);
  const [createForm, setCreateForm] = useState<TemplateForm>(EMPTY_FORM);

  /* Edit inline */
  const [editingId, setEditingId]   = useState<number | null>(null);
  const [editForm, setEditForm]     = useState<TemplateForm>(EMPTY_FORM);

  const load = useCallback(async () => {
    setError(null);
    try {
      setTemplates(await getTemplates());
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error al cargar plantillas.");
    }
  }, []);

  useEffect(() => { load(); }, [load]);

  /* ── Create ── */
  const handleCreate = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    try {
      const newT = await createTemplate(createForm);
      setTemplates((prev) => (prev ? [newT, ...prev] : [newT]));
      setCreateForm(EMPTY_FORM);
      setShowCreate(false);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error al crear.");
    } finally {
      setSaving(false);
    }
  };

  /* ── Edit ── */
  const startEdit = (t: Template) => {
    setEditingId(t.id);
    setEditForm({ name: t.name, subject: t.subject ?? "", body: t.body ?? "", channel: t.channel });
  };

  const handleUpdate = async (e: FormEvent) => {
    e.preventDefault();
    if (editingId === null) return;
    setSaving(true);
    try {
      const updated = await updateTemplate(editingId, editForm);
      setTemplates((prev) => prev?.map((t) => (t.id === editingId ? updated : t)) ?? null);
      setEditingId(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error al actualizar.");
    } finally {
      setSaving(false);
    }
  };

  /* ── Delete ── */
  const handleDelete = async (id: number) => {
    if (!window.confirm("¿Eliminar esta plantilla?")) return;
    try {
      await deleteTemplate(id);
      setTemplates((prev) => prev?.filter((t) => t.id !== id) ?? null);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error al eliminar.");
    }
  };

  return (
    <div className={styles.card}>
      <div className={styles.sectionHeaderRow}>
        <div>
          <p className={styles.sectionLabel}>Plantillas</p>
          <h2 className={styles.cardTitle}>Gestión de plantillas</h2>
          <p className={styles.cardDescription}>
            Crea y administra plantillas reutilizables para tus mensajes.
          </p>
        </div>
        <button
          className={styles.primaryButton}
          style={{ width: "auto", padding: "0.75rem 1.25rem" }}
          onClick={() => { setShowCreate((v) => !v); setEditingId(null); }}
        >
          <Plus size={15} style={{ display: "inline", marginRight: 5 }} />
          Nueva plantilla
        </button>
      </div>

      {error && <p className={styles.errorText} style={{ marginTop: "0.75rem" }}>{error}</p>}

      {/* ── Create form ── */}
      {showCreate && (
        <form onSubmit={handleCreate} className={styles.templateFormCard}>
          <h3>Nueva plantilla</h3>
          <div className={styles.formRow}>
            <input
              className={styles.input}
              placeholder="Nombre *"
              value={createForm.name}
              onChange={(e) => setCreateForm((f) => ({ ...f, name: e.target.value }))}
              required
            />
            <select
              className={styles.input}
              value={createForm.channel}
              onChange={(e) => setCreateForm((f) => ({ ...f, channel: e.target.value }))}
            >
              <option value="email">Email</option>
              <option value="whatsapp">WhatsApp</option>
            </select>
          </div>
          <input
            className={styles.input}
            placeholder="Asunto (solo para Email)"
            value={createForm.subject}
            onChange={(e) => setCreateForm((f) => ({ ...f, subject: e.target.value }))}
          />
          <textarea
            className={styles.textarea}
            placeholder="Cuerpo del mensaje *"
            rows={4}
            value={createForm.body}
            onChange={(e) => setCreateForm((f) => ({ ...f, body: e.target.value }))}
            required
          />
          <div className={styles.formActions}>
            <button type="button" className={styles.secondaryButton} onClick={() => setShowCreate(false)}>
              Cancelar
            </button>
            <button type="submit" className={styles.primaryButton} style={{ width: "auto", padding: "0.75rem 1.4rem" }} disabled={saving}>
              {saving ? "Guardando…" : "Guardar"}
            </button>
          </div>
        </form>
      )}

      {/* ── List ── */}
      <div className={styles.formSection} style={{ marginTop: "1rem" }}>
        {templates === null && !error && <p>Cargando plantillas…</p>}
        {templates && templates.length === 0 && <p>No hay plantillas guardadas aún.</p>}
        {templates && templates.length > 0 && (
          <ul className={styles.templateList}>
            {templates.map((t) =>
              editingId === t.id ? (
                /* ── Edit form inline ── */
                <li key={t.id} className={styles.templateItem}>
                  <form onSubmit={handleUpdate} style={{ display: "flex", flexDirection: "column", gap: "0.7rem" }}>
                    <div className={styles.formRow}>
                      <input
                        className={styles.input}
                        placeholder="Nombre *"
                        value={editForm.name}
                        onChange={(e) => setEditForm((f) => ({ ...f, name: e.target.value }))}
                        required
                      />
                      <select
                        className={styles.input}
                        value={editForm.channel}
                        onChange={(e) => setEditForm((f) => ({ ...f, channel: e.target.value }))}
                      >
                        <option value="email">Email</option>
                        <option value="whatsapp">WhatsApp</option>
                      </select>
                    </div>
                    <input
                      className={styles.input}
                      placeholder="Asunto"
                      value={editForm.subject}
                      onChange={(e) => setEditForm((f) => ({ ...f, subject: e.target.value }))}
                    />
                    <textarea
                      className={styles.textarea}
                      placeholder="Cuerpo del mensaje *"
                      rows={3}
                      value={editForm.body}
                      onChange={(e) => setEditForm((f) => ({ ...f, body: e.target.value }))}
                      required
                    />
                    <div className={styles.formActions}>
                      <button type="button" className={styles.secondaryButton} onClick={() => setEditingId(null)}>
                        Cancelar
                      </button>
                      <button type="submit" className={styles.primaryButton} style={{ width: "auto", padding: "0.7rem 1.25rem" }} disabled={saving}>
                        {saving ? "Guardando…" : "Actualizar"}
                      </button>
                    </div>
                  </form>
                </li>
              ) : (
                /* ── Display ── */
                <li key={t.id} className={styles.templateItem}>
                  <div className={styles.templateItemHeader}>
                    <div>
                      <strong>{t.name}</strong>
                      <div className={styles.templateMeta}>
                        <span className={`${styles.channelTag} ${t.channel === "email" ? styles.channelEmail : styles.channelWhatsapp}`}>
                          {t.channel}
                        </span>
                        {t.subject && <span style={{ marginLeft: 6, color: "var(--muted)", fontSize: "0.83rem" }}>{t.subject}</span>}
                      </div>
                    </div>
                    <div className={styles.templateItemActions}>
                      <button className={styles.ghostButton} onClick={() => startEdit(t)} title="Editar">
                        <Pencil size={13} style={{ display: "inline", marginRight: 3 }} />
                        Editar
                      </button>
                      <button className={styles.dangerButton} onClick={() => handleDelete(t.id)} title="Eliminar">
                        <Trash2 size={13} style={{ display: "inline", marginRight: 3 }} />
                        Eliminar
                      </button>
                    </div>
                  </div>
                  <p className={styles.templateContent}>{t.body}</p>
                </li>
              )
            )}
          </ul>
        )}
      </div>
    </div>
  );
}

/* ─────────────────────────────────────────────
   REPORTS
───────────────────────────────────────────── */

function ReportsSection() {
  const [kpis, setKpis]     = useState<KpisResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState<string | null>(null);
  const [exporting, setExporting] = useState<"pdf" | "excel" | null>(null);

  useEffect(() => {
    getReportsKpis()
      .then(setKpis)
      .catch((err) => setError(err instanceof Error ? err.message : "Error al cargar KPIs."))
      .finally(() => setLoading(false));
  }, []);

  const handleExport = async (format: "pdf" | "excel") => {
    setExporting(format);
    setError(null);
    try {
      await exportReport(format);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error al exportar.");
    } finally {
      setExporting(null);
    }
  };

  /* Datos para gráficas */
  const statusData = kpis
    ? Object.entries(kpis.status_counts).map(([name, value]) => ({ name, value }))
    : [];

  const channelData = kpis
    ? Object.entries(kpis.channel_counts).map(([name, value]) => ({ name, value }))
    : [];

  return (
    <div className={styles.card}>
      <p className={styles.sectionLabel}>Reportes</p>
      <h2 className={styles.cardTitle}>Reportes y análisis</h2>
      <p className={styles.cardDescription}>
        KPIs en tiempo real y exportación a PDF o Excel.
      </p>

      {loading && <p>Cargando indicadores…</p>}
      {error   && <p className={styles.errorText}>{error}</p>}

      {kpis && (
        <>
          {/* KPI cards */}
          <div className={styles.reportsGrid}>
            <div className={styles.reportStat}>
              <p>Total mensajes</p>
              <p>{kpis.total_messages}</p>
            </div>
            <div className={styles.reportStat}>
              <p>Mensajes pendientes</p>
              <p>{kpis.pending_messages}</p>
            </div>
            <div className={styles.reportStat}>
              <p>Plantillas activas</p>
              <p>{kpis.active_templates}</p>
            </div>
            <div className={styles.reportStat}>
              <p>Fallidos</p>
              <p>{kpis.status_counts["fallido"] ?? 0}</p>
            </div>
          </div>

          {/* Charts */}
          {(statusData.length > 0 || channelData.length > 0) && (
            <div className={styles.chartsGrid}>
              {statusData.length > 0 && (
                <div className={styles.chartCard}>
                  <h4>Mensajes por estado</h4>
                  <ResponsiveContainer width="100%" height={200}>
                    <BarChart data={statusData} margin={{ top: 4, right: 8, left: -20, bottom: 0 }}>
                      <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                      <XAxis dataKey="name" tick={{ fontSize: 11 }} />
                      <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                      <Tooltip />
                      <Bar dataKey="value" radius={[4, 4, 0, 0]}>
                        {statusData.map((entry) => (
                          <Cell key={entry.name} fill={STATUS_COLORS[entry.name] ?? "#a1a1aa"} />
                        ))}
                      </Bar>
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              )}

              {channelData.length > 0 && (
                <div className={styles.chartCard}>
                  <h4>Mensajes por canal</h4>
                  <ResponsiveContainer width="100%" height={200}>
                    <BarChart data={channelData} margin={{ top: 4, right: 8, left: -20, bottom: 0 }}>
                      <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                      <XAxis dataKey="name" tick={{ fontSize: 11 }} />
                      <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                      <Tooltip />
                      <Bar dataKey="value" radius={[4, 4, 0, 0]}>
                        {channelData.map((entry) => (
                          <Cell
                            key={entry.name}
                            fill={entry.name === "email" ? "#2563eb" : "#16a34a"}
                          />
                        ))}
                      </Bar>
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              )}
            </div>
          )}
        </>
      )}

      {/* Exportar */}
      <div className={styles.exportRow}>
        <button
          className={styles.exportButton}
          onClick={() => handleExport("pdf")}
          disabled={Boolean(exporting)}
        >
          {exporting === "pdf" ? "Descargando PDF…" : "Exportar PDF"}
        </button>
        <button
          className={styles.exportButton}
          onClick={() => handleExport("excel")}
          disabled={Boolean(exporting)}
        >
          {exporting === "excel" ? "Descargando Excel…" : "Exportar Excel"}
        </button>
      </div>
    </div>
  );
}
