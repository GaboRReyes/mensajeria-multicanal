"use client";

import { FormEvent, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import styles from "./login.module.css";
import { loginRequest } from "../../services/auth";

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
    const token = localStorage.getItem("token");
    if (token) {
      const stored = localStorage.getItem("user");
      const user = stored ? JSON.parse(stored) : null;
      const role = user?.role;
      if (role === "admin") router.replace("/pages/admin");
      else if (role === "developer") router.replace("/pages/dev");
      else router.replace("/pages/dashboard");
    }
  }, [router]);

  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      const data = await loginRequest({ email, password });
      localStorage.setItem("token", data.token);
      localStorage.setItem("user", JSON.stringify(data.user));
      const role = data.user.role;
      if (role === "admin") router.push("/pages/admin");
      else if (role === "developer") router.push("/pages/dev");
      else router.push("/pages/dashboard");
    } catch (err) {
      if (err instanceof Error) {
        setError(err.message);
      } else {
        setError("Error al iniciar sesión.");
      }
    } finally {
      setLoading(false);
    }
  };

  if (!mounted) return null;

  return (
    <div className={styles.page}>
      <div className={styles.container}>

        <div className={styles.info}>
          <p className={styles.badge}>Mensajería Multicanal</p>
          <h1 className={styles.title}>
            Controla la comunicación de tu equipo desde un solo lugar.
          </h1>
          <p className={styles.description}>
            Gestiona mensajes, notificaciones y envíos en una plataforma
            segura y profesional diseñada para equipos.
          </p>

          <div className={styles.features}>
            <div className={styles.feature}>
              <span className={styles.featureDot} />
              Integración de canales en tiempo real
            </div>
            <div className={styles.feature}>
              <span className={styles.featureDot} />
              Panel intuitivo y fácil de usar
            </div>
            <div className={styles.feature}>
              <span className={styles.featureDot} />
              Informes claros y rutas de respuesta rápidas
            </div>
          </div>
        </div>

        <div className={styles.formWrapper}>
          <div className={styles.formHeader}>
            <h2>Inicia sesión</h2>
            <p>Entra a tu cuenta para revisar todos tus canales y mensajes.</p>
          </div>

          <form onSubmit={handleSubmit} className={styles.form}>
            <div className={styles.field}>
              <label>Correo electrónico</label>
              <input
                type="email"
                placeholder="tu@correo.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>

            <div className={styles.field}>
              <label>Contraseña</label>
              <input
                type="password"
                placeholder="••••••••"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
              />
            </div>

            {error && (
              <div className={styles.error}>{error}</div>
            )}

            <button
              type="submit"
              disabled={loading}
              className={styles.button}
            >
              {loading ? "Entrando..." : "Acceder"}
            </button>
          </form>

          <div className={styles.footer}>
            ¿No tienes cuenta? Ponte en contacto con soporte para activar tu acceso.
          </div>
        </div>

      </div>
    </div>
  );
}