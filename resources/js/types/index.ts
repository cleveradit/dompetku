import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface FlashMessages {
    success?: string | null;
    error?: string | null;
}

export interface SharedData {
    name: string;
    auth: Auth;
    flash: FlashMessages;
    status?: string | null;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    currency: string;
    email_verified_at: string | null;
    [key: string]: unknown;
}

export type WalletType = 'cash' | 'bank' | 'ewallet' | 'other';
export type TransactionType = 'income' | 'expense' | 'transfer';
export type CategoryType = 'income' | 'expense';

export interface Wallet {
    id: number;
    name: string;
    type: WalletType;
    type_label: string;
    current_balance: string;
    color: string | null;
    icon: string | null;
    is_archived: boolean;
    deletable?: boolean;
}

export interface Category {
    id: number;
    name: string;
    type: CategoryType;
    color: string | null;
    icon: string | null;
    is_default: boolean;
}

export interface Transaction {
    id: number;
    type: TransactionType;
    amount: string;
    description: string | null;
    occurred_on: string;
    wallet: { id: number; name: string; color: string | null } | null;
    destination_wallet: { id: number; name: string; color: string | null } | null;
    category: { id: number; name: string; color: string | null; icon: string | null } | null;
    is_recurring: boolean;
    attachments_count?: number;
    attachments?: { id: number; original_name: string; mime_type: string; url: string }[];
}
