export type UserRole = 'admin' | 'client' | 'developer';

export interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    is_active: boolean;
    monthly_limit: number | null;
    used_this_month: number;
}

export interface LoginResponse {
    token: string;
    user: User;
}