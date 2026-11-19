export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Role = 'student' | 'donor' | 'admin' | 'super_admin' | 'none';

export type Auth = {
    user: User;
    role: Role;
};
