export type User = {
    id: number;
    name: string;
    email: string;
    role: 'user' | 'admin';
};

export type Auth = {
    user: User | null;
};
