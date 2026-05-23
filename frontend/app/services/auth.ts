import { LoginResponse } from "../types/auth";

interface LoginData {
    email: string;
    password: string;
}

export async function loginRequest(
    data: LoginData
): Promise<LoginResponse> {

    const response = await fetch(
        "http://127.0.0.1:8000/api/v1/login",
        {
            method: "POST",

            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },

            body: JSON.stringify(data)
        }
    );

    const result = await response.json();

    if (!response.ok) {
        throw new Error(
            result.message || "Error al iniciar sesión"
        );
    }

    return result;
}