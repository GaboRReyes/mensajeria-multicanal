"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import {
  LayoutDashboard,
  Mail,
  FileText,
  BarChart3,
  Settings,
  LogOut,
  Menu,
  X,
} from "lucide-react";
import styles from "./dashboard.module.css";

type Section = "home" | "messages" | "templates" | "reports" | "settings";

export default function Dashboard() {
  const router = useRouter();
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [activeSection, setActiveSection] = useState<Section>("home");
  const [sidebarOpen, setSidebarOpen] = useState(false);

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
    { id: "home" as Section, label: "Inicio", icon: LayoutDashboard },
    { id: "messages" as Section, label: "Mensajes", icon: Mail },
    { id: "templates" as Section, label: "Plantillas", icon: FileText },
    { id: "reports" as Section, label: "Reportes", icon: BarChart3 },
    { id: "settings" as Section, label: "Configuración", icon: Settings },
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
        className={`${styles.overlay} ${sidebarOpen ? styles.visible : ""}`}
        onClick={() => setSidebarOpen(false)}
      />

      {/* Sidebar */}
      <aside className={`${styles.sidebar} ${sidebarOpen ? styles.open : ""}`}>
        <div className={styles.sidebarHeader}>
          <span className={styles.sidebarTitle}>Panel</span>
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
                  activeSection === item.id ? styles.active : ""
                }`}
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

      {/* Contenido principal */}
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

        {/* Secciones */}
        <main className={styles.content}>
          {activeSection === "home"      && <HomeSection user={user} />}
          {activeSection === "messages"  && <MessagesSection />}
          {activeSection === "templates" && <TemplatesSection />}
          {activeSection === "reports"   && <ReportsSection />}
          {activeSection === "settings"  && <SettingsSection />}
        </main>

      </div>
    </div>
  );
}

/* ─── Secciones ─────────────────────────────────────────────── */

function HomeSection({ user }: { user: any }) {
  return (
    <>
      <div className={styles.card}>
        <p className={styles.sectionLabel}>Bienvenido</p>
        <h2 className={styles.cardTitle}>
          Hola, {user?.name || "Usuario"}
        </h2>
        <p className={styles.cardDescription}>
          Desde este panel puedes gestionar mensajes, plantillas,
          ver reportes y configurar los ajustes de tu plataforma.
        </p>
      </div>

      <div className={styles.statsGrid}>
        <div className={styles.statCard}>
          <p className={styles.statLabel}>Mensajes enviados</p>
          <p className={styles.statValue}>1,248</p>
        </div>
        <div className={styles.statCard}>
          <p className={styles.statLabel}>Plantillas activas</p>
          <p className={styles.statValue}>8</p>
        </div>
        <div className={styles.statCard}>
          <p className={styles.statLabel}>Canales conectados</p>
          <p className={styles.statValue}>5</p>
        </div>
      </div>
    </>
  );
}

function MessagesSection() {
  return (
    <div className={styles.card}>
      <p className={styles.sectionLabel}>Mensajes</p>
      <h2 className={styles.cardTitle}>Gestión de mensajes</h2>
      <p className={styles.cardDescription}>
        Envía, monitorea y cancela mensajes en tiempo real.
      </p>

      <div className={styles.formSection}>
        <div className={styles.formCard}>
          <h3>Enviar nuevo mensaje</h3>
          <div className={styles.inputGroup}>
            <input
              type="text"
              placeholder="Destinatario"
              className={styles.input}
            />
            <textarea
              placeholder="Contenido del mensaje"
              className={styles.textarea}
              rows={4}
            />
            <button className={styles.primaryButton}>
              Enviar
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

function TemplatesSection() {
  return (
    <div className={styles.card}>
      <p className={styles.sectionLabel}>Plantillas</p>
      <h2 className={styles.cardTitle}>Plantillas de mensajes</h2>
      <p className={styles.cardDescription}>
        Crea y gestiona plantillas para reutilizar en tus mensajes.
      </p>

      <div className={styles.formSection}>
        <button className={styles.primaryButton}>
          + Nueva plantilla
        </button>

        <div className={styles.formCard}>
          <h3>Plantillas guardadas</h3>
          <p>No hay plantillas guardadas aún.</p>
        </div>
      </div>
    </div>
  );
}

function ReportsSection() {
  return (
    <div className={styles.card}>
      <p className={styles.sectionLabel}>Reportes</p>
      <h2 className={styles.cardTitle}>Reportes y análisis</h2>
      <p className={styles.cardDescription}>
        Visualiza KPIs y exporta datos en diferentes formatos.
      </p>

      <div className={styles.reportsGrid}>
        <div className={styles.reportStat}>
          <p>Total de mensajes</p>
          <p>2,456</p>
        </div>
        <div className={styles.reportStat}>
          <p>Tasa de entrega</p>
          <p>98.5%</p>
        </div>
      </div>

      <div style={{ marginTop: "1.25rem" }}>
        <button className={styles.primaryButton} style={{ width: "auto", padding: "0.8rem 1.5rem" }}>
          Exportar datos
        </button>
      </div>
    </div>
  );
}

function SettingsSection() {
  return (
    <div className={styles.card}>
      <p className={styles.sectionLabel}>Configuración</p>
      <h2 className={styles.cardTitle}>Configuración</h2>
      <p className={styles.cardDescription}>
        Gestiona los canales y configuraciones de envío de mensajes.
      </p>

      <div className={styles.formSection}>

        <div className={styles.formCard}>
          <h3>Configuración de SMTP</h3>
          <p>Para envíos de correo electrónico</p>
          <div className={styles.inputGroup}>
            <input type="text"     placeholder="Host SMTP"         className={styles.input} />
            <input type="number"   placeholder="Puerto"            className={styles.input} />
            <input type="email"    placeholder="Correo de origen"  className={styles.input} />
            <input type="password" placeholder="Contraseña"        className={styles.input} />
            <button className={styles.primaryButton}>
              Guardar configuración SMTP
            </button>
          </div>
        </div>

        <div className={styles.formCard}>
          <h3>Configuración de WhatsApp</h3>
          <p>Integración con WhatsApp Business</p>
          <div className={styles.inputGroup}>
            <input type="text" placeholder="API Key de WhatsApp"  className={styles.input} />
            <input type="text" placeholder="Número de teléfono"   className={styles.input} />
            <button className={styles.primaryButton}>
              Guardar configuración WhatsApp
            </button>
          </div>
        </div>

        <div className={styles.formCard}>
          <h3>Configuración general</h3>
          <p>Ajustes generales de la plataforma</p>
          <div className={styles.inputGroup}>
            <input type="text"  placeholder="Nombre de la empresa" className={styles.input} />
            <input type="email" placeholder="Correo de soporte"    className={styles.input} />
            <button className={styles.primaryButton}>
              Guardar configuración general
            </button>
          </div>
        </div>

      </div>
    </div>
  );
}