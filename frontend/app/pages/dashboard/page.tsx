"use client";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";

export default function Dashboard() {
  const router = useRouter();

  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);

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

  if (loading) {
    return (
      <div className="dashboard-loading">
        Cargando...
      </div>
    );
  }

  return (
    <div className="dashboard-page">

      <header className="dashboard-header">
        <div className="dashboard-header-content">

          <div>
            <p className="dashboard-badge">
              Mensajería Multicanal
            </p>

            <h1 className="dashboard-title">
              Panel de control
            </h1>
          </div>

          <div className="dashboard-user">

            <div className="dashboard-user-name">
              {user?.name || "Usuario"}
            </div>

            <button
              onClick={handleLogout}
              className="dashboard-logout"
            >
              Cerrar sesión
            </button>

          </div>
        </div>
      </header>

      <main className="dashboard-main">

        <section className="dashboard-grid">

          <div>

            <div className="card">
              <div className="flex-between">
                <div>
                  <p className="card-title">
                    Bienvenido
                  </p>

                  <h2 className="hero-title">
                    Hola, {user?.name || "Usuario"}
                  </h2>
                </div>

                <div className="status-badge">
                  Cuenta activa
                </div>
              </div>

              <p className="hero-description">
                Desde aquí puedes revisar mensajes, monitorear el estado
                de tus envíos y gestionar tus canales de comunicación
                sin complicaciones.
              </p>
            </div>

            <div className="stats-grid">

              <div className="stat-card">
                <p className="stat-label">
                  Mensajes recientes
                </p>

                <p className="stat-value">
                  1,248
                </p>
              </div>

              <div className="stat-card">
                <p className="stat-label">
                  Canales conectados
                </p>

                <p className="stat-value">
                  8
                </p>
              </div>

              <div className="stat-card">
                <p className="stat-label">
                  Alertas nuevas
                </p>

                <p className="stat-value">
                  3
                </p>
              </div>

            </div>
          </div>

          <aside className="sidebar">

            <div className="card">
              <p className="card-title">
                Resumen rápido
              </p>

              <div className="list">

                <div className="list-item">
                  <span>Respuestas pendientes</span>
                  <strong>14</strong>
                </div>

                <div className="list-item">
                  <span>Mensajes sin leer</span>
                  <strong>72</strong>
                </div>

                <div className="list-item">
                  <span>Canales activos</span>
                  <strong>5</strong>
                </div>

              </div>
            </div>

            <div className="card">
              <p className="card-title">
                Accesos recientes
              </p>

              <div className="list">
                <div className="simple-item">
                  Último inicio: hoy a las 09:12
                </div>

                <div className="simple-item">
                  IP: 192.168.1.21
                </div>
              </div>
            </div>

          </aside>

        </section>

      </main>
    </div>
  );
}