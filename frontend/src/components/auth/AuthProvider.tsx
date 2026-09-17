"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { AuthApiError, currentCustomer, loginCustomer, logoutCustomer, registerCustomer } from "@/services/auth-api";
import type { Customer } from "@/types/auth";

type AuthContextValue = {
  customer: Customer | null;
  loading: boolean;
  login: typeof loginCustomer;
  register: typeof registerCustomer;
  logout: () => Promise<void>;
  setCustomer: (customer: Customer) => void;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [customer, setCustomerState] = useState<Customer | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    currentCustomer().then(setCustomerState).catch((error) => {
      if (!(error instanceof AuthApiError) || error.status !== 401) console.error(error);
    }).finally(() => setLoading(false));
  }, []);

  const login = useCallback(async (input: { email: string; password: string; remember: boolean }) => {
    const result = await loginCustomer(input); setCustomerState(result); return result;
  }, []);
  const register = useCallback(async (input: Record<string, string>) => {
    const result = await registerCustomer(input); setCustomerState(result); return result;
  }, []);
  const logout = useCallback(async () => { await logoutCustomer(); setCustomerState(null); }, []);
  const value = useMemo(() => ({ customer, loading, login, register, logout, setCustomer: setCustomerState }), [customer, loading, login, register, logout]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) throw new Error("useAuth must be used inside AuthProvider");
  return context;
}
