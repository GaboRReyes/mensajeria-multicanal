  "use client";

  import { FormEvent, useEffect, useState } from "react";
  import { useRouter } from "next/navigation";

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
        router.replace("/pages/dashboard");
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

        router.push("/pages/dashboard");
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

    if (!mounted) {
      return null;
    }

    return (<div className="login-page">
  <div className="login-container">

    <div className="login-info">
      <p className="login-badge">
        Mensajería Multicanal
      </p>

      <h1 className="login-title">
        Controla la comunicación de tu equipo desde un solo lugar.
      </h1>

      <p className="login-description">
        Gestiona mensajes, notificaciones y envíos en una plataforma segura y profesional diseñada para equipos.
      </p>

      <div className="login-features">
        <div className="login-feature">
          <span></span>
          Integración de canales en tiempo real
        </div>

        <div className="login-feature">
          <span></span>
          Panel intuitivo y fácil de usar
        </div>

        <div className="login-feature">
          <span></span>
          Informes claros y rutas de respuesta rápidas
        </div>
      </div>
    </div>

    <div className="login-form-wrapper">
      <div className="login-form-header">
        <h2>Inicia sesión</h2>

        <p>
          Entrar a tu cuenta para revisar todos tus canales y mensajes.
        </p>
      </div>

      <form onSubmit={handleSubmit} className="login-form">

        <div className="form-group">
          <label>Correo electrónico</label>

          <input
            type="email"
            placeholder="tu@correo.com"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
          />
        </div>

        <div className="form-group">
          <label>Contraseña</label>

          <input
            type="password"
            placeholder="********"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
        </div>

        {error && (
          <div className="login-error">
            {error}
          </div>
        )}

        <button
          type="submit"
          disabled={loading}
          className="login-button"
        >
          {loading ? "Entrando..." : "Acceder"}
        </button>
      </form>

      <div className="login-footer">
        ¿No tienes cuenta? Ponte en contacto con soporte para activar tu acceso.
      </div>
    </div>

  </div>
</div>
);
  }
