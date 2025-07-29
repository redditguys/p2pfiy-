import { User } from "@shared/schema";

interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
}

class AuthManager {
  private state: AuthState = {
    user: null,
    isAuthenticated: false
  };

  private listeners: Array<(state: AuthState) => void> = [];

  getState(): AuthState {
    return this.state;
  }

  subscribe(listener: (state: AuthState) => void) {
    this.listeners.push(listener);
    return () => {
      const index = this.listeners.indexOf(listener);
      if (index > -1) {
        this.listeners.splice(index, 1);
      }
    };
  }

  private notify() {
    this.listeners.forEach(listener => listener(this.state));
  }

  setUser(user: User | null) {
    this.state = {
      user,
      isAuthenticated: !!user
    };
    this.notify();
  }

  logout() {
    this.setUser(null);
  }

  isAdmin(): boolean {
    return this.state.user?.role === 'admin';
  }

  isClient(): boolean {
    return this.state.user?.role === 'client';
  }

  isWorker(): boolean {
    return this.state.user?.role === 'worker';
  }
}

export const authManager = new AuthManager();

// Hook for React components
import { useState, useEffect } from 'react';

export function useAuth() {
  const [authState, setAuthState] = useState(authManager.getState());

  useEffect(() => {
    return authManager.subscribe(setAuthState);
  }, []);

  return authState;
}
