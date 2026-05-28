"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import {
  Key,
  BookOpen,
  LogOut,
  Menu,
  X,
  Plus,
  Trash2,
  RefreshCw,
  Copy,
  Check,
} from "lucide-react";

import {
  getDevApiKeys,
  createDevApiKey,
  deleteDevApiKey,
  revokeDevApiKey,
} from "../../services/api";

import type { ApiKey, CreatedApiKey, CreateApiKeyPayload } from "../../services/api";
import styles from "./dev.module.css";

type Section = "keys" | "docs";

/* ─────────────────────────────────────────────
   Copy helper
───────────────────────────────────────────── */
function CopyButton({ text }: { text: string }) {
  const [copied, setCopied] = useState(false);
  const handle = async () => {
    await navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };
  return (
    <button className={styles.secondaryButton} onClick={handle} title="Copiar">
      {copied ? <Check size={13} color="#16a34a" /> : <Copy size={13} />}
    </button>
  );
}

/* ─────────────────────────────────────────────
   Create Key Modal
───────────────────────────────────────────── */
interface CreateKeyModalProps {
  onClose: () => void;
  onCreated: (result: CreatedApiKey) => void;
}

function CreateKeyModal({ onClose, onCreated }: CreateKeyModalProps) {
  const [name, setName] = useState("");
  const [env, setEnv] = useState<"live" | "test">("live");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      const payload: CreateApiKeyPayload = {
        name,
        env,
        abilities: ["messages:write", "messages:read"],
      };
      const result = await createDevApiKey(payload);
      onCreated(result);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error inesperado.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className={styles.modalOverlay} onClick={onClose}>
      <div className={styles.modal} onClick={(e) => e.stopPropagation()}>
        <h3>Nueva API Key</h3>
        <form onSubmit={handleSubmit} style={{ display: "flex", flexDirection: "column", gap: "0.85rem" }}>
          <div className={styles.inputGroup}>
            <label>Nombre descriptivo</label>
            <input
              className={styles.input}
              placeholder="ej. Producción App"
              value={name}
              onChange={(e) => setName(e.target.value)}
              required
            />
          </div>
          <div className={styles.inputGroup}>
            <label>Entorno</label>
            <select className={styles.select} value={env} onChange={(e) => setEnv(e.target.value as "live" | "test")}>
              <option value="live">Live</option>
              <option value="test">Test</option>
            </select>
          </div>
          {error && <p className={styles.errorText}>{error}</p>}
          <div className={styles.modalActions}>
            <button type="button" className={styles.secondaryButton} onClick={onClose}>Cancelar</button>
            <button type="submit" className={styles.primaryButton} disabled={loading}>
              {loading ? "Creando…" : "Crear API Key"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ─────────────────────────────────────────────
   API Keys section
───────────────────────────────────────────── */
function KeysSection() {
  const [keys, setKeys] = useState<ApiKey[]>([]);
  const [loading, setLoading] = useState(false);
  const [showCreate, setShowCreate] = useState(false);
  const [newKey, setNewKey] = useState<CreatedApiKey | null>(null);
  const [actionLoading, setActionLoading] = useState<number | null>(null);
  const [toDelete, setToDelete] = useState<ApiKey | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await getDevApiKeys();
      setKeys(data);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);

  const handleRevoke = async (key: ApiKey) => {
    setActionLoading(key.id);
    try {
      await revokeDevApiKey(key.id);
      load();
    } catch (err) {
      console.error(err);
    } finally {
      setActionLoading(null);
    }
  };

  const handleDelete = async () => {
    if (!toDelete) return;
    setActionLoading(toDelete.id);
    try {
      await deleteDevApiKey(toDelete.id);
      setToDelete(null);
      load();
    } catch (err) {
      console.error(err);
    } finally {
      setActionLoading(null);
    }
  };

  const handleCreated = (result: CreatedApiKey) => {
    setShowCreate(false);
    setNewKey(result);
    load();
  };

  return (
    <>
      <div className={styles.card}>
        <div className={styles.sectionHeaderRow}>
          <div>
            <p className={styles.sectionLabel}>Autenticación REST</p>
            <p className={styles.cardTitle}>API Keys</p>
            <p className={styles.cardDescription}>
              Genera claves para autenticar tus llamadas REST desde cualquier aplicación. Máximo 10 activas.
            </p>
          </div>
          <button className={styles.primaryButton} onClick={() => setShowCreate(true)}>
            <Plus size={15} /> Nueva API Key
          </button>
        </div>

        {/* Token recién creado */}
        {newKey && (
          <div className={styles.tokenAlert}>
            <p>⚠️ {newKey.warning}</p>
            <div style={{ display: "flex", gap: "0.75rem", alignItems: "center" }}>
              <code className={styles.tokenCode}>{newKey.token}</code>
              <CopyButton text={newKey.token} />
            </div>
            <button
              className={styles.secondaryButton}
              style={{ alignSelf: "flex-start", marginTop: "0.25rem" }}
              onClick={() => setNewKey(null)}
            >
              Entendido, ya la guardé
            </button>
          </div>
        )}

        {loading ? (
          <div className={styles.loading}>Cargando…</div>
        ) : (
          <div className={styles.tableWrapper}>
            <table className={styles.table}>
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Prefijo</th>
                  <th>Estado</th>
                  <th>Permisos</th>
                  <th>Último uso</th>
                  <th>Expira</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                {keys.map((k) => (
                  <tr key={k.id}>
                    <td>{k.name}</td>
                    <td style={{ fontFamily: "monospace", fontSize: "0.85rem" }}>{k.prefix}…</td>
                    <td>
                      <span className={`${styles.activeBadge} ${k.is_active ? styles.activeBadgeOn : styles.activeBadgeOff}`}>
                        {k.is_active ? "Activa" : "Revocada"}
                      </span>
                    </td>
                    <td style={{ fontSize: "0.82rem", color: "var(--muted)" }}>{k.abilities.join(", ")}</td>
                    <td style={{ fontSize: "0.82rem", color: "var(--muted)" }}>
                      {k.last_used_at ? new Date(k.last_used_at).toLocaleDateString("es") : "—"}
                    </td>
                    <td style={{ fontSize: "0.82rem", color: "var(--muted)" }}>
                      {k.expires_at ? new Date(k.expires_at).toLocaleDateString("es") : "Sin fecha"}
                    </td>
                    <td>
                      <div className={styles.actionsGroup}>
                        {k.is_active && (
                          <button
                            className={styles.secondaryButton}
                            onClick={() => handleRevoke(k)}
                            disabled={actionLoading === k.id}
                            title="Revocar"
                          >
                            Revocar
                          </button>
                        )}
                        <button
                          className={styles.dangerButton}
                          onClick={() => setToDelete(k)}
                          disabled={actionLoading === k.id}
                          title="Eliminar"
                        >
                          <Trash2 size={13} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
                {keys.length === 0 && (
                  <tr>
                    <td colSpan={7} style={{ textAlign: "center", color: "var(--muted)", padding: "2.5rem" }}>
                      No tienes API Keys. Crea una para empezar.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        )}

        <div style={{ display: "flex", justifyContent: "flex-end", marginTop: "0.75rem" }}>
          <button className={styles.secondaryButton} onClick={load}>
            <RefreshCw size={14} />
          </button>
        </div>
      </div>

      {showCreate && <CreateKeyModal onClose={() => setShowCreate(false)} onCreated={handleCreated} />}

      {toDelete && (
        <div className={styles.modalOverlay} onClick={() => setToDelete(null)}>
          <div className={styles.modal} onClick={(e) => e.stopPropagation()}>
            <h3>Eliminar API Key</h3>
            <p style={{ color: "var(--muted)", fontSize: "0.92rem" }}>
              ¿Seguro que deseas eliminar <strong>{toDelete.name}</strong>? Las apps que usen esta key perderán acceso inmediatamente.
            </p>
            <div className={styles.modalActions}>
              <button className={styles.secondaryButton} onClick={() => setToDelete(null)}>Cancelar</button>
              <button
                className={styles.primaryButton}
                style={{ background: "#dc2626" }}
                onClick={handleDelete}
                disabled={actionLoading === toDelete.id}
              >
                {actionLoading === toDelete.id ? "Eliminando…" : "Eliminar"}
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}

/* ─────────────────────────────────────────────
   Docs section
───────────────────────────────────────────── */
function DocsSection() {
  const base = "http://127.0.0.1:8000/api/v1/api";

  return (
    <div className={styles.card}>
      <p className={styles.sectionLabel}>Referencia</p>
      <p className={styles.cardTitle}>API REST</p>
      <p className={styles.cardDescription}>
        Autentícate con tu API Key en el header <code>Authorization: Bearer sk_live_…</code>.
        Todos los endpoints devuelven JSON.
      </p>

      <div className={styles.docSection}>
        <h4>Enviar un mensaje</h4>
        <p>POST {base}/messages</p>
        <pre className={styles.codeBlock}>{`curl -X POST ${base}/messages \\
  -H "Authorization: Bearer sk_live_TUTOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{
    "channel": "whatsapp",
    "to": "+5491112345678",
    "body": "Hola desde la API"
  }'`}</pre>
      </div>

      <div className={styles.docSection}>
        <h4>Listar mensajes</h4>
        <p>GET {base}/messages</p>
        <pre className={styles.codeBlock}>{`curl ${base}/messages \\
  -H "Authorization: Bearer sk_live_TUTOKEN"`}</pre>
      </div>

      <div className={styles.docSection}>
        <h4>Ver un mensaje</h4>
        <p>GET {base}/messages/{"{uuid}"}</p>
        <pre className={styles.codeBlock}>{`curl ${base}/messages/550e8400-e29b-41d4... \\
  -H "Authorization: Bearer sk_live_TUTOKEN"`}</pre>
      </div>

      <div className={styles.docSection}>
        <h4>Permisos disponibles</h4>
        <pre className={styles.codeBlock}>{`messages:write   → enviar mensajes
messages:read    → leer mensajes y logs`}</pre>
      </div>
    </div>
  );
}

/* ─────────────────────────────────────────────
   Dev Dashboard
───────────────────────────────────────────── */

export default function DevDashboard() {
  const router = useRouter();
  const [user, setUser] = useState<{ name: string; email: string } | null>(null);
  const [loading, setLoading] = useState(true);
  const [activeSection, setActiveSection] = useState<Section>("keys");
  const [sidebarOpen, setSidebarOpen] = useState(false);

  useEffect(() => {
    const token = localStorage.getItem("token");
    const userData = localStorage.getItem("user");
    if (!token) { router.push("/pages/login"); return; }
    const parsed = userData ? JSON.parse(userData) : null;
    if (parsed?.role !== "developer" && parsed?.role !== "admin") {
      if (parsed?.role === "admin") router.replace("/pages/admin");
      else router.replace("/pages/dashboard");
      return;
    }
    setUser(parsed);
    setLoading(false);
  }, [router]);

  const handleLogout = () => {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    router.push("/pages/login");
  };

  const menuItems: { id: Section; label: string; icon: React.ElementType }[] = [
    { id: "keys",  label: "API Keys",    icon: Key },
    { id: "docs",  label: "Documentación", icon: BookOpen },
  ];

  if (loading) return <div className={styles.loading}>Cargando…</div>;

  return (
    <div className={styles.layout}>
      <div
        className={`${styles.overlay} ${sidebarOpen ? styles.visible : ""}`}
        onClick={() => setSidebarOpen(false)}
      />

      <aside className={`${styles.sidebar} ${sidebarOpen ? styles.open : ""}`}>
        <div className={styles.sidebarHeader}>
          <span className={styles.sidebarTitle}>Dev Panel</span>
          <span className={styles.roleBadge}>Dev</span>
        </div>

        <nav className={styles.sidebarNav}>
          <ul className={styles.sidebarMenu} style={{ listStyle: "none", padding: 0, margin: 0 }}>
            {menuItems.map((item) => {
              const Icon = item.icon;
              return (
                <li key={item.id}>
                  <button
                    className={`${styles.sidebarItem} ${activeSection === item.id ? styles.active : ""}`}
                    onClick={() => { setActiveSection(item.id); setSidebarOpen(false); }}
                  >
                    <Icon size={17} />
                    {item.label}
                  </button>
                </li>
              );
            })}
          </ul>
        </nav>

        <div className={styles.sidebarFooter}>
          <button className={styles.logoutButton} onClick={handleLogout}>
            <LogOut size={17} /> Cerrar sesión
          </button>
        </div>
      </aside>

      <div className={styles.main}>
        <header className={styles.header}>
          <button className={styles.menuButton} onClick={() => setSidebarOpen(!sidebarOpen)}>
            {sidebarOpen ? <X size={20} /> : <Menu size={20} />}
          </button>
          <span className={styles.userPill}>{user?.name} · developer</span>
        </header>

        <main className={styles.content}>
          {activeSection === "keys" && <KeysSection />}
          {activeSection === "docs" && <DocsSection />}
        </main>
      </div>
    </div>
  );
}
