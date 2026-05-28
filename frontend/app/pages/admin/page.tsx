"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import {
  LayoutDashboard,
  Users,
  BarChart3,
  LogOut,
  Menu,
  X,
  Plus,
  Pencil,
  Trash2,
  ToggleLeft,
  ToggleRight,
  RefreshCw,
} from "lucide-react";

import {
  getAdminStats,
  getAdminUsers,
  createAdminUser,
  updateAdminUser,
  deleteAdminUser,
  toggleAdminUser,
} from "../../services/api";

import type { AdminStats, AdminUser, PaginatedUsers, CreateUserPayload } from "../../services/api";
import styles from "./admin.module.css";

type Section = "stats" | "users";

/* ─────────────────────────────────────────────
   Role badge helper
───────────────────────────────────────────── */
function RoleBadge({ role }: { role: string }) {
  const cls =
    role === "admin"
      ? styles.roleBadgeAdmin
      : role === "developer"
      ? styles.roleBadgeDev
      : styles.roleBadgeClient;
  const label = role === "client" ? "usuario" : role;
  return <span className={cls}>{label}</span>;
}

/* ─────────────────────────────────────────────
   User form modal
───────────────────────────────────────────── */
interface UserModalProps {
  initial?: AdminUser | null;
  onClose: () => void;
  onSaved: () => void;
}

function UserModal({ initial, onClose, onSaved }: UserModalProps) {
  const [name, setName] = useState(initial?.name ?? "");
  const [email, setEmail] = useState(initial?.email ?? "");
  const [password, setPassword] = useState("");
  const [role, setRole] = useState<CreateUserPayload["role"]>(
    (initial?.role as CreateUserPayload["role"]) ?? "client"
  );
  const [limit, setLimit] = useState<string>(
    initial?.monthly_limit != null ? String(initial.monthly_limit) : ""
  );
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const isEdit = !!initial;

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      const payload: Partial<CreateUserPayload> = {
        name,
        email,
        role,
        monthly_limit: limit !== "" ? Number(limit) : null,
      };
      if (password) payload.password = password;
      if (isEdit) {
        await updateAdminUser(initial!.id, payload);
      } else {
        if (!password) { setError("La contraseña es requerida."); setLoading(false); return; }
        await createAdminUser({ ...payload, password } as CreateUserPayload);
      }
      onSaved();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error inesperado.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className={styles.modalOverlay} onClick={onClose}>
      <div className={styles.modal} onClick={(e) => e.stopPropagation()}>
        <h3>{isEdit ? "Editar usuario" : "Nuevo usuario"}</h3>

        <form onSubmit={handleSubmit} style={{ display: "flex", flexDirection: "column", gap: "0.85rem" }}>
          <div className={styles.formRow}>
            <div className={styles.inputGroup}>
              <label>Nombre</label>
              <input className={styles.input} value={name} onChange={(e) => setName(e.target.value)} required placeholder="Nombre completo" />
            </div>
            <div className={styles.inputGroup}>
              <label>Email</label>
              <input className={styles.input} type="email" value={email} onChange={(e) => setEmail(e.target.value)} required placeholder="correo@dominio.com" />
            </div>
          </div>

          <div className={styles.formRow}>
            <div className={styles.inputGroup}>
              <label>{isEdit ? "Nueva contraseña (opcional)" : "Contraseña"}</label>
              <input className={styles.input} type="password" value={password} onChange={(e) => setPassword(e.target.value)} placeholder="••••••••" />
            </div>
            <div className={styles.inputGroup}>
              <label>Rol</label>
              <select className={styles.select} value={role} onChange={(e) => setRole(e.target.value as CreateUserPayload["role"])}>
                <option value="client">Usuario</option>
                <option value="admin">Admin</option>
                <option value="developer">Desarrollador</option>
              </select>
            </div>
          </div>

          <div className={styles.inputGroup}>
            <label>Límite mensual de mensajes (vacío = ilimitado)</label>
            <input className={styles.input} type="number" min="0" value={limit} onChange={(e) => setLimit(e.target.value)} placeholder="ej. 1000" />
          </div>

          {error && <p className={styles.errorText}>{error}</p>}

          <div className={styles.modalActions}>
            <button type="button" className={styles.secondaryButton} onClick={onClose}>
              Cancelar
            </button>
            <button type="submit" className={styles.primaryButton} disabled={loading}>
              {loading ? "Guardando…" : isEdit ? "Guardar cambios" : "Crear usuario"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ─────────────────────────────────────────────
   Stats section
───────────────────────────────────────────── */
function StatsSection({ stats }: { stats: AdminStats | null }) {
  if (!stats) {
    return <div className={styles.loading}>Cargando estadísticas…</div>;
  }

  return (
    <>
      <div className={styles.card}>
        <p className={styles.sectionLabel}>Usuarios</p>
        <div className={styles.statsGrid}>
          <div className={styles.statCard}>
            <p className={styles.statLabel}>Total usuarios</p>
            <p className={styles.statValue}>{stats.users.total}</p>
          </div>
          <div className={styles.statCard}>
            <p className={styles.statLabel}>Usuarios activos</p>
            <p className={styles.statValue}>{stats.users.active}</p>
          </div>
          <div className={styles.statCard}>
            <p className={styles.statLabel}>Total mensajes</p>
            <p className={styles.statValue}>{stats.messages.total}</p>
          </div>
          <div className={styles.statCard}>
            <p className={styles.statLabel}>Campañas</p>
            <p className={styles.statValue}>{stats.campaigns.total}</p>
          </div>
        </div>
        <div className={styles.miniStatsRow}>
          {Object.entries(stats.users.by_role).map(([r, n]) => (
            <span key={r} className={styles.miniStat}>
              <strong>{n}</strong>{r === "client" ? "usuarios" : r === "developer" ? "devs" : "admins"}
            </span>
          ))}
        </div>
      </div>

      <div className={styles.card}>
        <p className={styles.sectionLabel}>Mensajes por estado</p>
        <div className={styles.miniStatsRow}>
          {Object.entries(stats.messages.by_status).map(([s, n]) => (
            <span key={s} className={styles.miniStat}><strong>{n}</strong>{s}</span>
          ))}
        </div>
        <p className={styles.sectionLabel} style={{ marginTop: "1rem" }}>Por canal</p>
        <div className={styles.miniStatsRow}>
          {Object.entries(stats.messages.by_channel).map(([c, n]) => (
            <span key={c} className={styles.miniStat}><strong>{n}</strong>{c}</span>
          ))}
        </div>
      </div>

      {Object.keys(stats.campaigns.by_status).length > 0 && (
        <div className={styles.card}>
          <p className={styles.sectionLabel}>Campañas por estado</p>
          <div className={styles.miniStatsRow}>
            {Object.entries(stats.campaigns.by_status).map(([s, n]) => (
              <span key={s} className={styles.miniStat}><strong>{n}</strong>{s}</span>
            ))}
          </div>
        </div>
      )}
    </>
  );
}

/* ─────────────────────────────────────────────
   Users section
───────────────────────────────────────────── */
function UsersSection() {
  const [paginated, setPaginated] = useState<PaginatedUsers | null>(null);
  const [search, setSearch] = useState("");
  const [roleFilter, setRoleFilter] = useState("");
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(false);
  const [modalUser, setModalUser] = useState<AdminUser | null | undefined>(undefined);
  const [toDelete, setToDelete] = useState<AdminUser | null>(null);
  const [actionLoading, setActionLoading] = useState<number | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await getAdminUsers({ role: roleFilter || undefined, search: search || undefined, page });
      setPaginated(data);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [search, roleFilter, page]);

  useEffect(() => { load(); }, [load]);

  const handleToggle = async (user: AdminUser) => {
    setActionLoading(user.id);
    try {
      await toggleAdminUser(user.id);
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
      await deleteAdminUser(toDelete.id);
      setToDelete(null);
      load();
    } catch (err) {
      console.error(err);
    } finally {
      setActionLoading(null);
    }
  };

  return (
    <>
      <div className={styles.card}>
        <div className={styles.sectionHeaderRow}>
          <div>
            <p className={styles.sectionLabel}>Gestión de usuarios</p>
            <p className={styles.cardTitle}>Usuarios</p>
          </div>
          <button className={styles.primaryButton} onClick={() => setModalUser(null)}>
            <Plus size={15} /> Nuevo usuario
          </button>
        </div>

        <div className={styles.filterBar}>
          <input
            className={styles.input}
            placeholder="Buscar por nombre o email…"
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
          />
          <select
            className={styles.select}
            value={roleFilter}
            onChange={(e) => { setRoleFilter(e.target.value); setPage(1); }}
          >
            <option value="">Todos los roles</option>
            <option value="admin">Admin</option>
            <option value="client">Usuario</option>
            <option value="developer">Desarrollador</option>
          </select>
          <button className={styles.secondaryButton} onClick={load}>
            <RefreshCw size={14} />
          </button>
        </div>

        {loading ? (
          <div className={styles.loading}>Cargando…</div>
        ) : (
          <div className={styles.tableWrapper}>
            <table className={styles.table}>
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Email</th>
                  <th>Rol</th>
                  <th>Estado</th>
                  <th>Mensajes</th>
                  <th>Uso / Límite</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                {paginated?.data.map((u) => (
                  <tr key={u.id}>
                    <td>{u.name}</td>
                    <td style={{ color: "var(--muted)", fontSize: "0.88rem" }}>{u.email}</td>
                    <td><RoleBadge role={u.role} /></td>
                    <td>
                      <span className={`${styles.activeBadge} ${u.is_active ? styles.activeBadgeOn : styles.activeBadgeOff}`}>
                        {u.is_active ? "Activo" : "Inactivo"}
                      </span>
                    </td>
                    <td>{u.messages_count ?? "—"}</td>
                    <td style={{ fontSize: "0.85rem", color: "var(--muted)" }}>
                      {u.used_this_month} / {u.monthly_limit ?? "∞"}
                    </td>
                    <td>
                      <div className={styles.actionsGroup}>
                        <button
                          className={styles.secondaryButton}
                          title="Editar"
                          onClick={() => setModalUser(u)}
                        >
                          <Pencil size={13} />
                        </button>
                        <button
                          className={styles.secondaryButton}
                          title={u.is_active ? "Desactivar" : "Activar"}
                          onClick={() => handleToggle(u)}
                          disabled={actionLoading === u.id}
                        >
                          {u.is_active ? <ToggleRight size={15} color="#16a34a" /> : <ToggleLeft size={15} color="#6b7280" />}
                        </button>
                        <button
                          className={styles.dangerButton}
                          title="Eliminar"
                          onClick={() => setToDelete(u)}
                          disabled={actionLoading === u.id}
                        >
                          <Trash2 size={13} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
                {paginated?.data.length === 0 && (
                  <tr><td colSpan={7} style={{ textAlign: "center", color: "var(--muted)", padding: "2rem" }}>Sin resultados</td></tr>
                )}
              </tbody>
            </table>
          </div>
        )}

        {paginated && paginated.last_page > 1 && (
          <div className={styles.pagination}>
            <button className={styles.pageBtn} onClick={() => setPage((p) => p - 1)} disabled={page === 1}>← Anterior</button>
            <span>Página {page} de {paginated.last_page}</span>
            <button className={styles.pageBtn} onClick={() => setPage((p) => p + 1)} disabled={page === paginated.last_page}>Siguiente →</button>
          </div>
        )}
      </div>

      {/* Create / Edit modal */}
      {modalUser !== undefined && (
        <UserModal
          initial={modalUser}
          onClose={() => setModalUser(undefined)}
          onSaved={() => { setModalUser(undefined); load(); }}
        />
      )}

      {/* Delete confirm */}
      {toDelete && (
        <div className={styles.modalOverlay} onClick={() => setToDelete(null)}>
          <div className={styles.modal} onClick={(e) => e.stopPropagation()}>
            <h3>Eliminar usuario</h3>
            <p style={{ color: "var(--muted)", fontSize: "0.92rem" }}>
              ¿Seguro que deseas eliminar a <strong>{toDelete.name}</strong>? Esta acción no se puede deshacer.
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
   Admin Dashboard
───────────────────────────────────────────── */

export default function AdminDashboard() {
  const router = useRouter();
  const [user, setUser] = useState<{ name: string; email: string } | null>(null);
  const [loading, setLoading] = useState(true);
  const [activeSection, setActiveSection] = useState<Section>("stats");
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [stats, setStats] = useState<AdminStats | null>(null);

  useEffect(() => {
    const token = localStorage.getItem("token");
    const userData = localStorage.getItem("user");
    if (!token) { router.push("/pages/login"); return; }
    const parsed = userData ? JSON.parse(userData) : null;
    if (parsed?.role !== "admin") {
      if (parsed?.role === "developer") router.replace("/pages/dev");
      else router.replace("/pages/dashboard");
      return;
    }
    setUser(parsed);
    setLoading(false);
  }, [router]);

  useEffect(() => {
    if (!loading) {
      getAdminStats().then(setStats).catch(console.error);
    }
  }, [loading]);

  const handleLogout = () => {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    router.push("/pages/login");
  };

  const menuItems: { id: Section; label: string; icon: React.ElementType }[] = [
    { id: "stats", label: "Estadísticas", icon: BarChart3 },
    { id: "users", label: "Usuarios", icon: Users },
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
          <span className={styles.sidebarTitle}>Panel Admin</span>
          <span className={styles.roleBadge}>Admin</span>
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

      {/* Main */}
      <div className={styles.main}>
        <header className={styles.header}>
          <button className={styles.menuButton} onClick={() => setSidebarOpen(!sidebarOpen)}>
            {sidebarOpen ? <X size={20} /> : <Menu size={20} />}
          </button>
          <span className={styles.userPill}>{user?.name} · admin</span>
        </header>

        <main className={styles.content}>
          {activeSection === "stats" && <StatsSection stats={stats} />}
          {activeSection === "users" && <UsersSection />}
        </main>
      </div>
    </div>
  );
}
