import { useState } from "react";
import { useNavigate } from "react-router-dom";

import { loginRequest } from "../services/auth";

export default function Login() {

    const navigate = useNavigate();

    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");

    const [error, setError] = useState("");

    const handleSubmit = async (
        e: React.FormEvent<HTMLFormElement>
    ) => {

        e.preventDefault();

        setError("");

        try {

            const data = await loginRequest({
                email,
                password
            });


            localStorage.setItem(
                "token",
                data.token
            );

            localStorage.setItem(
                "user",
                JSON.stringify(data.user)
            );

            navigate("/dashboard");

        } catch (err) {

            if (err instanceof Error) {
                setError(err.message);
            }
        }
    };

    return (

        <div className="min-h-screen flex items-center justify-center bg-gray-100">

            <form
                onSubmit={handleSubmit}
                className="bg-white p-8 rounded-xl shadow-md w-96"
            >

                <h1 className="text-2xl font-bold mb-6 text-center">
                    Iniciar Sesión
                </h1>

                <input
                    type="email"
                    placeholder="Correo"
                    value={email}
                    onChange={(e) =>
                        setEmail(e.target.value)
                    }
                    className="w-full border p-3 rounded mb-4"
                />

                <input
                    type="password"
                    placeholder="Contraseña"
                    value={password}
                    onChange={(e) =>
                        setPassword(e.target.value)
                    }
                    className="w-full border p-3 rounded mb-4"
                />

                <button
                    type="submit"
                    className="w-full bg-blue-600 text-white p-3 rounded"
                >
                    Entrar
                </button>

                {error && (
                    <p className="text-red-500 mt-4 text-center">
                        {error}
                    </p>
                )}

            </form>

        </div>
    );
}