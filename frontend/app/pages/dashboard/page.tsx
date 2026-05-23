"use client";

import { useRouter } from "next/navigation";
import { FormEvent, useEffect, useState } from "react";
import {
  LayoutDashboard,
  Mail,
  FileText,
  BarChart3,
  LogOut,
  Menu,
  X,
} from "lucide-react";

import {
  exportReport,
  getReportsKpis,
  sendMessage,
  getTemplates,
} from "../../services/api";

import type { Template } from "../../services/api";

import styles from "./dashboard.module.css";

type Section = "home" | "messages" | "templates" | "reports";

type Kpis = {
  total_messages: number;
  active_templates: number;
  connected_channels: number;
  success_rate: string;
  failed_messages: number;
};

export default function Dashboard() {
  const router = useRouter();

  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  const [activeSection, setActiveSection] =
    useState<Section>("home");

  const [sidebarOpen, setSidebarOpen] = useState(false);

  const [kpis, setKpis] = useState<Kpis | null>(null);

  /* ───────────────────────── KPIs ───────────────────────── */

  useEffect(() => {
    getReportsKpis()
      .then((data) => setKpis(data))
      .catch((err) => console.error(err));
  }, []);

  /* ───────────────────────── Auth ───────────────────────── */

  useEffect(() => {
    const token = localStorage.getItem("token");
    const userData = localStorage.getItem("user");

    if (!token) {
      router.push("/pages/login");
      return;
    }

    if (userData) {
      setUser(JSON.parse(userData));
    }

    setLoading(false);
  }, [router]);

  const handleLogout = () => {
    localStorage.removeItem("token");
    localStorage.removeItem("user");

    router.push("/pages/login");
  };

  const menuItems = [
    {
      id: "home" as Section,
      label: "Inicio",
      icon: LayoutDashboard,
    },
    {
      id: "messages" as Section,
      label: "Mensajes",
      icon: Mail,
    },
    {
      id: "reports" as Section,
      label: "Reportes",
      icon: BarChart3,
    },
  ];

  if (loading) {
    return (
      <div className={styles.loading}>
        Cargando...
      </div>
    );
  }

  return (
    <div className={styles.layout}>
      {/* Overlay móvil */}
      <div
        className={`${styles.overlay} ${
          sidebarOpen ? styles.visible : ""
        }`}
        onClick={() => setSidebarOpen(false)}
      />

      {/* Sidebar */}
      <aside
        className={`${styles.sidebar} ${
          sidebarOpen ? styles.open : ""
        }`}
      >
        <div className={styles.sidebarHeader}>
          <span className={styles.sidebarTitle}>
            Panel
          </span>

          <button
            onClick={() => setSidebarOpen(false)}
            className={styles.menuButton}
            aria-label="Cerrar menú"
          >
            <X size={18} />
          </button>
        </div>

        <nav className={styles.sidebarNav}>
          <div className={styles.sidebarMenu}>
            {menuItems.map((item) => (
              <button
                key={item.id}
                onClick={() => {
                  setActiveSection(item.id);
                  setSidebarOpen(false);
                }}
                className={`${styles.sidebarItem} ${
                  activeSection === item.id
                    ? styles.active
                    : ""
                }`}
              >
                <item.icon size={17} />
                <span>{item.label}</span>
              </button>
            ))}
          </div>
        </nav>

        <div className={styles.sidebarFooter}>
          <button
            onClick={handleLogout}
            className={styles.logoutButton}
          >
            <LogOut size={17} />
            <span>Cerrar sesión</span>
          </button>
        </div>
      </aside>

      {/* Main */}
      <div className={styles.main}>
        {/* Header */}
        <header className={styles.header}>
          <button
            onClick={() => setSidebarOpen(true)}
            className={styles.menuButton}
            aria-label="Abrir menú"
          >
            <Menu size={20} />
          </button>

          <span className={styles.userPill}>
            {user?.name || "Usuario"}
          </span>
        </header>

        {/* Content */}
        <main className={styles.content}>
          {activeSection === "home" && (
            <HomeSection
              user={user}
              kpis={kpis}
            />
          )}

          {activeSection === "messages" && (
            <MessagesSection />
          )}

          {activeSection === "templates" && (
            <TemplatesSection />
          )}

          {activeSection === "reports" && (
            <ReportsSection />
          )}
        </main>
      </div>
    </div>
  );
}

/* ───────────────────────── HOME ───────────────────────── */

function HomeSection({
  user,
  kpis,
}: {
  user: any;
  kpis: Kpis | null;
}) {
  return (
    <>
      <div className={styles.card}>
        <p className={styles.sectionLabel}>
          Bienvenido
        </p>

        <h2 className={styles.cardTitle}>
          Hola, {user?.name || "Usuario"}
        </h2>

        <p className={styles.cardDescription}>
          Desde este panel puedes gestionar
          mensajes, plantillas, ver reportes y
          configurar los ajustes de tu plataforma.
        </p>
      </div>

      <div className={styles.statsGrid}>
        <div className={styles.statCard}>
          <p className={styles.statLabel}>
            Mensajes enviados
          </p>

          <p className={styles.statValue}>
            {kpis?.total_messages ?? 0}
          </p>
        </div>

        <div className={styles.statCard}>
          <p className={styles.statLabel}>
            Plantillas activas
          </p>

          <p className={styles.statValue}>
            {kpis?.active_templates ?? 0}
          </p>
        </div>

        <div className={styles.statCard}>
          <p className={styles.statLabel}>
            Canales conectados
          </p>

          <p className={styles.statValue}>
            {kpis?.connected_channels ?? 0}
          </p>
        </div>
      </div>
    </>
  );
}

function MessagesSection() {
  const [recipient, setRecipient] = useState("");
  const [content, setContent] = useState("");

  const [channel, setChannel] = useState<
    "whatsapp" | "email" | "both"
  >("whatsapp");

  const [scheduledAt, setScheduledAt] =
    useState("");

  const [status, setStatus] = useState<
    string | null
  >(null);

  const [error, setError] = useState<
    string | null
  >(null);

  const [sending, setSending] =
    useState(false);

  /*
  ─────────────────────────────────────────────
  Templates
  ─────────────────────────────────────────────
  */

  const [templates, setTemplates] = useState<
    Template[]
  >([]);

  const [templatesOpen, setTemplatesOpen] =
    useState(false);

  const [templatesError, setTemplatesError] =
    useState<string | null>(null);

  const toggleTemplates = async () => {
    if (
      !templatesOpen &&
      templates.length === 0
    ) {
      try {
        const data = await getTemplates();

        setTemplates(data);

      } catch (err) {

        setTemplatesError(
          err instanceof Error
            ? err.message
            : "Error al cargar plantillas."
        );
      }
    }

    setTemplatesOpen((prev) => !prev);
  };

  const applyTemplate = (t: Template) => {

    setContent(t.content);

    if (
      t.channel === "whatsapp" ||
      t.channel === "email" ||
      t.channel === "both"
    ) {

      setChannel(
        t.channel as
          | "whatsapp"
          | "email"
          | "both"
      );
    }

    setTemplatesOpen(false);
  };

  /*
  ─────────────────────────────────────────────
  SEND MESSAGE
  ─────────────────────────────────────────────
  */

  const handleSend = async (
    event: FormEvent<HTMLFormElement>
  ) => {

    event.preventDefault();

    setStatus(null);

    setError(null);

    setSending(true);

    try {

      const response = await sendMessage({

        recipient,

        content,

        channel,

        scheduled_at:
          scheduledAt || null,
      });

      setStatus(

        scheduledAt

          ? "Mensaje programado correctamente"

          : `Mensaje enviado por ${
              response.channel === "both"
                ? "WhatsApp y Email"
                : response.channel ===
                  "whatsapp"
                ? "WhatsApp"
                : "Email"
            }`
      );

      setRecipient("");

      setContent("");

      setScheduledAt("");

    } catch (err) {

      setError(
        err instanceof Error
          ? err.message
          : "Error al enviar el mensaje."
      );

    } finally {

      setSending(false);
    }
  };

  return (
    <div className={styles.card}>

      <p className={styles.sectionLabel}>
        Mensajes
      </p>

      <h2 className={styles.cardTitle}>
        Gestión de mensajes
      </h2>

      <p className={styles.cardDescription}>
        Envía mensajes por WhatsApp,
        Email o ambos canales.
      </p>

      <div className={styles.formSection}>

        <div className={styles.formCard}>

          <div className={styles.formCardHeader}>

            <h3>
              Enviar nuevo mensaje
            </h3>

            {/* Templates */}

            <div
              className={
                styles.templateDropdownWrapper
              }
            >

              <button
                type="button"
                className={
                  styles.templateToggle
                }
                onClick={toggleTemplates}
              >

                <FileText size={15} />

                Usar plantilla

                <span
                  className={`${styles.chevron} ${
                    templatesOpen
                      ? styles.chevronUp
                      : ""
                  }`}
                >
                  ▾
                </span>

              </button>

              {templatesOpen && (

                <div
                  className={
                    styles.templateDropdown
                  }
                >

                  {templatesError && (

                    <p
                      className={
                        styles.errorText
                      }
                      style={{
                        padding:
                          "0.6rem 0.9rem",
                        margin: 0,
                      }}
                    >
                      {templatesError}
                    </p>
                  )}

                  {!templatesError &&
                    templates.length ===
                      0 && (

                    <p
                      className={
                        styles.infoText
                      }
                      style={{
                        padding:
                          "0.6rem 0.9rem",
                        margin: 0,
                      }}
                    >
                      No hay plantillas
                      disponibles.
                    </p>
                  )}

                  {templates.map((t) => (

                    <button
                      key={t.id}
                      type="button"
                      className={
                        styles.templateOption
                      }
                      onClick={() =>
                        applyTemplate(t)
                      }
                    >

                      <span
                        className={
                          styles.templateOptionName
                        }
                      >
                        {t.name}
                      </span>

                      <span
                        className={
                          styles.templateOptionMeta
                        }
                      >
                        {t.channel}

                        {t.subject
                          ? ` · ${t.subject}`
                          : ""}
                      </span>

                    </button>
                  ))}
                </div>
              )}
            </div>
          </div>

          {/* FORM */}

          <form
            onSubmit={handleSend}
            className={styles.inputGroup}
          >

            <input
              type="text"
              value={recipient}
              onChange={(e) =>
                setRecipient(
                  e.target.value
                )
              }
              placeholder="Destinatario"
              className={styles.input}
              required
            />

            <textarea
              value={content}
              onChange={(e) =>
                setContent(
                  e.target.value
                )
              }
              placeholder="Contenido del mensaje — o selecciona una plantilla arriba"
              className={styles.textarea}
              rows={4}
              required
            />

            {/* CHANNELS */}

            <div
              className={styles.fieldGroup}
            >

              {(
                [
                  "whatsapp",
                  "email",
                  "both",
                ] as const
              ).map((ch) => (

                <label
                  key={ch}
                  className={
                    styles.radioLabel
                  }
                >

                  <input
                    type="radio"
                    value={ch}
                    checked={
                      channel === ch
                    }
                    onChange={() =>
                      setChannel(ch)
                    }
                  />

                  {ch === "whatsapp"
                    ? "WhatsApp"
                    : ch === "email"
                    ? "Email"
                    : "Ambos"}

                </label>
              ))}
            </div>

            {/* PROGRAMACIÓN */}

            <div
              className={
                styles.scheduleGroup
              }
            >

              <label
                className={
                  styles.scheduleLabel
                }
              >
                Programar envío
              </label>

              <input
                type="datetime-local"
                value={scheduledAt}
                onChange={(e) =>
                  setScheduledAt(
                    e.target.value
                  )
                }
                className={styles.input}
                min={new Date(
                Date.now() - new Date().getTimezoneOffset() * 60000
)
                  .toISOString()
                  .slice(0, 16)}
              />
            </div>

            {/* STATUS */}

            {status && (

              <p
                className={
                  styles.successText
                }
              >
                {status}
              </p>
            )}

            {error && (

              <p
                className={
                  styles.errorText
                }
              >
                {error}
              </p>
            )}

            {/* BUTTON */}

            <button
              type="submit"
              className={
                styles.primaryButton
              }
              disabled={sending}
            >

              {sending
                ? "Procesando..."
                : scheduledAt
                ? "Programar mensaje"
                : "Enviar"}

            </button>
          </form>
        </div>
      </div>
    </div>
  );
}

function TemplatesSection() {
  const [templates, setTemplates] = useState<Template[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    getTemplates()
      .then((data) => setTemplates(data))
      .catch((err) =>
        setError(err instanceof Error ? err.message : "Error al cargar plantillas.")
      );
  }, []);

  return (
    <div className={styles.card}>
      <p className={styles.sectionLabel}>Plantillas</p>
      <h2 className={styles.cardTitle}>Plantillas de mensajes</h2>
      <p className={styles.cardDescription}>
        Crea y gestiona plantillas para reutilizar en tus mensajes.
      </p>

      <div className={styles.formSection}>
        <button className={styles.primaryButton} style={{ width: "auto", padding: "0.8rem 1.5rem" }}>
          + Nueva plantilla
        </button>

        <div className={styles.formCard}>
          <h3>Plantillas guardadas</h3>
          {error && <p className={styles.errorText}>{error}</p>}
          {templates === null && !error && <p>Cargando plantillas...</p>}
          {templates && templates.length === 0 && (
            <p>No hay plantillas guardadas aún.</p>
          )}
          {templates && templates.length > 0 && (
            <ul className={styles.templateList}>
              {templates.map((t) => (
                <li key={t.id} className={styles.templateItem}>
                  <strong>{t.name}</strong>
                  <div className={styles.templateMeta}>
                    {t.channel}
                    {t.subject ? ` · ${t.subject}` : ""}
                  </div>
                  <p className={styles.templateContent}>{t.content}</p>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>
    </div>
  );
}

function ReportsSection() {
  const [kpis, setKpis] = useState<any | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [exporting, setExporting] = useState<"pdf" | "excel" | null>(null);

  useEffect(() => {
    getReportsKpis()
      .then((data) => setKpis(data))
      .catch((err) =>
        setError(err instanceof Error ? err.message : "Error al cargar KPIs.")
      ) 
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

  return (
    <div className={styles.card}>
      <p className={styles.sectionLabel}>Reportes</p>
      <h2 className={styles.cardTitle}>Reportes y análisis</h2>
      <p className={styles.cardDescription}>
        Visualiza KPIs desde el backend y descarga reportes en PDF o Excel.
      </p>

      {loading && <p>Cargando indicadores...</p>}
      {error && <p className={styles.errorText}>{error}</p>}

      {kpis && (
        <div className={styles.reportsGrid}>
          <div className={styles.reportStat}>
            <p>Total de mensajes</p>
            <p>{kpis.total_messages}</p>
          </div>
          <div className={styles.reportStat}>
            <p>Tasa de entrega</p>
            <p>{kpis.success_rate}</p>
          </div>
          <div className={styles.reportStat}>
            <p>Mensajes fallidos</p>
            <p>{kpis.failed_messages}</p>
          </div>
          <div className={styles.reportStat}>
            <p>Plantillas activas</p>
            <p>{kpis.active_templates}</p>
          </div>
        </div>
      )}

      <div style={{ marginTop: "1.25rem" }}>
        <button
          className={styles.primaryButton}
          style={{ width: "auto", padding: "0.8rem 1.5rem", marginRight: "0.75rem" }}
          onClick={() => handleExport("pdf")}
          disabled={Boolean(exporting)}
        >
          {exporting === "pdf" ? "Descargando PDF..." : "Exportar PDF"}
        </button>
        <button
          className={styles.primaryButton}
          style={{ width: "auto", padding: "0.8rem 1.5rem" }}
          onClick={() => handleExport("excel")}
          disabled={Boolean(exporting)}
        >
          {exporting === "excel" ? "Descargando Excel..." : "Exportar Excel"}
        </button>
      </div>
    </div>
  );
}