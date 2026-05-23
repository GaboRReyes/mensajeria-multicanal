"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";

export default function App() {
    const router = useRouter();
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        setMounted(true);
        const token = localStorage.getItem("token");
        
        if (!token) {
            router.push("/pages/login");
        } else {
            router.push("/pages/dashboard");
        }
    }, [router]);

    if (!mounted) return null;

    return (
        <div className="min-h-screen flex items-center justify-center">
            <p>Redirigiendo...</p>
        </div>
    );
}