// Pinia store for authentication state and session actions.
// Replaces the previous module-level useAuth composable.
import { defineStore } from "pinia";
import { apiClient, fetchCsrfCookie } from "@shared/services/apiClient";
import { deserializeDocument, type JsonApiDocument, type JsonApiResource } from "@shared/types/api";
import type { User } from "../models/User";

interface AuthState {
  user: User | null;
  initialized: boolean;
}

export const useAuthStore = defineStore("auth", {
  state: (): AuthState => ({
    user: null,
    initialized: false,
  }),

  getters: {
    // Checks whether the current user has a given permission.
    // Returns false (fail-closed) when not authenticated yet.
    can:
      (state) => (permission: string): boolean => {
        return state.user?.permissions?.includes(permission) ?? false;
      },
  },

  actions: {
    // Fetches the current authenticated user (if any). Called once by
    // the router guard before the first navigation.
    async checkAuth(): Promise<User | null> {
      try {
        this.user = await apiClient.getResource<Omit<User, "id" | "type">>("/auth/me");
      } catch {
        this.user = null;
      } finally {
        this.initialized = true;
      }

      return this.user;
    },

    // Logs a user in: fetches the CSRF cookie, then authenticates via Sanctum.
    async login(email: string, password: string): Promise<void> {
      await fetchCsrfCookie();
      const result = await apiClient.post<JsonApiDocument<JsonApiResource<Omit<User, "id" | "type">>>>("/auth/login", {
        email,
        password,
      });
      const user = deserializeDocument(result);
      if (!user) throw new Error("The login response did not include a user resource.");
      this.user = user;
    },

    // Logs the current user out and clears local state.
    async logout(): Promise<void> {
      await apiClient.post<void>("/auth/logout");
      this.user = null;
    },
  },
});
