"use client";
import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";
import {
  addCartItem,
  getCart,
  CartApiError,
  removeCartItem,
  updateCartItem,
} from "./api";
import type { Cart } from "./types";

type CartContextValue = {
  cart: Cart | null;
  loading: boolean;
  error: string | null;
  drawerOpen: boolean;
  openDrawer: () => void;
  closeDrawer: () => void;
  addItem: (variantPublicId: string, quantity: number) => Promise<void>;
  updateItem: (id: number, quantity: number) => Promise<void>;
  removeItem: (id: number) => Promise<void>;
  refresh: () => Promise<void>;
};
const CartContext = createContext<CartContextValue | null>(null);

export function CartProvider({ children }: { children: React.ReactNode }) {
  const [cart, setCart] = useState<Cart | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [drawerOpen, setDrawerOpen] = useState(false);
  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setCart(await getCart());
    } catch (caught) {
      setError(
        caught instanceof Error ? caught.message : "Unable to load your cart.",
      );
    } finally {
      setLoading(false);
    }
  }, []);
  useEffect(() => {
    let active = true;
    getCart()
      .then((next) => {
        if (active) setCart(next);
      })
      .catch((caught) => {
        if (active)
          setError(
            caught instanceof Error
              ? caught.message
              : "Unable to load your cart.",
          );
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, []);
  const mutate = useCallback(async (action: () => Promise<Cart | void>) => {
    setError(null);
    try {
      const next = await action();
      if (next) setCart(next);
    } catch (caught) {
      const message =
        caught instanceof CartApiError || caught instanceof Error
          ? caught.message
          : "Unable to update your cart.";
      setError(message);
      throw caught;
    }
  }, []);
  const value = useMemo(
    () => ({
      cart,
      loading,
      error,
      drawerOpen,
      openDrawer: () => setDrawerOpen(true),
      closeDrawer: () => setDrawerOpen(false),
      addItem: (variant: string, quantity: number) =>
        mutate(() => addCartItem(variant, quantity)),
      updateItem: (id: number, quantity: number) =>
        mutate(() => updateCartItem(id, quantity)),
      removeItem: async (id: number) => {
        await mutate(() => removeCartItem(id));
        await refresh();
      },
      refresh,
    }),
    [cart, loading, error, drawerOpen, mutate, refresh],
  );
  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}
export function useCart() {
  const context = useContext(CartContext);
  if (!context) throw new Error("useCart must be used inside CartProvider");
  return context;
}
