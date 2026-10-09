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

type UserDocument = JsonApiDocument<JsonApiResource<Omit<User, "id" | "type">>>;

// Outcome of the password step: either a session was started, or the
// account has two-factor enabled and the challenge must be completed.
export interface LoginResult {
  twoFactorRequired: boolean;
}

export interface TwoFactorChallengeInput {
  code?: string;
  recoveryCode?: string;
}

export interface ResetPasswordPayload {
  token: string;
  email: string;
  password: string;
  passwordConfirmation: string;
}

// The API answers a two-factor-protected login with a meta-only document
// (no `data`) instead of the user resource.
function isTwoFactorChallenge(document: unknown): boolean {
  if (typeof document !== "object" || document === null || "data" in document) return false;
  const meta = (document as { meta?: { two_factor_required?: unknown } }).meta;
  return meta?.two_factor_required === true;
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
    // `remember` asks for a persistent ("remember me") session. When the
    // account has two-factor enabled no user is set yet and the caller must
    // continue with completeTwoFactorChallenge().
    async login(email: string, password: string, remember = false): Promise<LoginResult> {
      await fetchCsrfCookie();
      const result = await apiClient.post<UserDocument | { meta: { two_factor_required: boolean } }>("/auth/login", {
        email,
        password,
        remember,
      });

      if (isTwoFactorChallenge(result)) return { twoFactorRequired: true };

      this.setUserFromDocument(result as UserDocument);
      return { twoFactorRequired: false };
    },

    // Second step of a two-factor login: an authenticator code or a recovery code.
    async completeTwoFactorChallenge(input: TwoFactorChallengeInput): Promise<void> {
      const body = input.recoveryCode ? { recovery_code: input.recoveryCode } : { code: input.code };
      const result = await apiClient.post<UserDocument>("/auth/two-factor-challenge", body);

      this.setUserFromDocument(result);
    },

    // Asks the API to email a password reset link. The API answers the same
    // way for unknown addresses, so success never confirms an account exists.
    async forgotPassword(email: string): Promise<void> {
      await fetchCsrfCookie();
      await apiClient.post<void>("/auth/forgot-password", { email });
    },

    // Sets a new password using the token from the reset email.
    async resetPassword(payload: ResetPasswordPayload): Promise<void> {
      await fetchCsrfCookie();
      await apiClient.post<void>("/auth/reset-password", {
        token: payload.token,
        email: payload.email,
        password: payload.password,
        password_confirmation: payload.passwordConfirmation,
      });
    },

    // Logs the current user out and clears local state.
    async logout(): Promise<void> {
      await apiClient.post<void>("/auth/logout");
      this.user = null;
    },

    setUserFromDocument(document: UserDocument): void {
      const user = deserializeDocument(document);
      if (!user) throw new Error("The login response did not include a user resource.");
      this.user = user;
    },
  },
});
