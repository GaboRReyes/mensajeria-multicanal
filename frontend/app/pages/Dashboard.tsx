import { User } from "../types/auth";

export default function Dashboard() {

    const user: User = JSON.parse(
        localStorage.getItem("user") || "{}"
    );

    return (

        <div className="p-10">

            <h1 className="text-3xl font-bold">
                Bienvenido {user.name}
            </h1>

        </div>
    );
}