import type { Express } from "express";
import { createServer, type Server } from "http";
import { storage } from "./storage";
import { z } from "zod";
import { insertTransactionSchema, insertDisputeSchema, insertPayoutSchema, insertPlatformSettingsSchema } from "@shared/schema";

export async function registerRoutes(app: Express): Promise<Server> {
  
  // Admin authentication
  app.post("/api/admin/login", async (req, res) => {
    try {
      const { email, password } = req.body;
      
      if (!email || !password) {
        return res.status(400).json({ message: "Email and password are required" });
      }

      const admin = await storage.authenticateAdmin(email, password);
      if (!admin) {
        return res.status(401).json({ message: "Invalid credentials" });
      }

      // In a real app, you'd set up session/JWT here
      res.json({ 
        user: { 
          id: admin.id, 
          email: admin.email, 
          username: admin.username, 
          role: admin.role 
        } 
      });
    } catch (error) {
      res.status(500).json({ message: "Authentication failed" });
    }
  });

  // Dashboard stats
  app.get("/api/admin/stats", async (req, res) => {
    try {
      const allTransactions = await storage.getAllTransactions();
      const allUsers = await storage.getAllUsers();
      const pendingDisputes = await storage.getDisputesByStatus("open");
      
      const totalRevenue = allTransactions
        .filter(t => t.status === "completed")
        .reduce((sum, t) => sum + parseFloat(t.amount), 0);
      
      const activeTransactions = allTransactions.filter(t => t.status === "pending").length;
      const activeUsers = allUsers.filter(u => u.isActive && u.role !== "admin").length;

      res.json({
        totalRevenue: totalRevenue.toFixed(2),
        activeTransactions,
        pendingDisputes: pendingDisputes.length,
        activeUsers,
      });
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch stats" });
    }
  });

  // Transaction management
  app.get("/api/admin/transactions", async (req, res) => {
    try {
      const { status, search } = req.query;
      let transactions = await storage.getAllTransactions();
      
      if (status && status !== "all") {
        transactions = transactions.filter(t => t.status === status);
      }
      
      if (search) {
        const searchTerm = search.toString().toLowerCase();
        transactions = transactions.filter(t => 
          t.id.toLowerCase().includes(searchTerm) ||
          t.client.email.toLowerCase().includes(searchTerm) ||
          t.worker.email.toLowerCase().includes(searchTerm)
        );
      }
      
      res.json(transactions);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch transactions" });
    }
  });

  app.get("/api/admin/transactions/:id", async (req, res) => {
    try {
      const transaction = await storage.getTransactionWithUsers(req.params.id);
      if (!transaction) {
        return res.status(404).json({ message: "Transaction not found" });
      }
      res.json(transaction);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch transaction" });
    }
  });

  app.patch("/api/admin/transactions/:id", async (req, res) => {
    try {
      const updates = req.body;
      const transaction = await storage.updateTransaction(req.params.id, updates);
      if (!transaction) {
        return res.status(404).json({ message: "Transaction not found" });
      }
      res.json(transaction);
    } catch (error) {
      res.status(500).json({ message: "Failed to update transaction" });
    }
  });

  app.post("/api/admin/transactions", async (req, res) => {
    try {
      const validatedData = insertTransactionSchema.parse(req.body);
      const transaction = await storage.createTransaction(validatedData);
      res.status(201).json(transaction);
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ message: "Invalid transaction data", errors: error.errors });
      }
      res.status(500).json({ message: "Failed to create transaction" });
    }
  });

  // User management
  app.get("/api/admin/users", async (req, res) => {
    try {
      const { role } = req.query;
      let users = await storage.getAllUsers();
      
      if (role && role !== "all") {
        users = users.filter(u => u.role === role);
      }
      
      // Remove sensitive data
      const safeUsers = users.map(({ password, ...user }) => user);
      res.json(safeUsers);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch users" });
    }
  });

  app.patch("/api/admin/users/:id", async (req, res) => {
    try {
      const updates = req.body;
      // Remove password from updates if present (should be handled separately)
      delete updates.password;
      
      const user = await storage.updateUser(req.params.id, updates);
      if (!user) {
        return res.status(404).json({ message: "User not found" });
      }
      
      const { password, ...safeUser } = user;
      res.json(safeUser);
    } catch (error) {
      res.status(500).json({ message: "Failed to update user" });
    }
  });

  // Dispute management
  app.get("/api/admin/disputes", async (req, res) => {
    try {
      const { status } = req.query;
      let disputes = await storage.getAllDisputes();
      
      if (status && status !== "all") {
        disputes = disputes.filter(d => d.status === status);
      }
      
      res.json(disputes);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch disputes" });
    }
  });

  app.patch("/api/admin/disputes/:id", async (req, res) => {
    try {
      const updates = req.body;
      const dispute = await storage.updateDispute(req.params.id, updates);
      if (!dispute) {
        return res.status(404).json({ message: "Dispute not found" });
      }
      res.json(dispute);
    } catch (error) {
      res.status(500).json({ message: "Failed to update dispute" });
    }
  });

  app.post("/api/admin/disputes", async (req, res) => {
    try {
      const validatedData = insertDisputeSchema.parse(req.body);
      const dispute = await storage.createDispute(validatedData);
      res.status(201).json(dispute);
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ message: "Invalid dispute data", errors: error.errors });
      }
      res.status(500).json({ message: "Failed to create dispute" });
    }
  });

  // Payout management
  app.get("/api/admin/payouts", async (req, res) => {
    try {
      const { status } = req.query;
      let payouts = await storage.getAllPayouts();
      
      if (status && status !== "all") {
        payouts = payouts.filter(p => p.status === status);
      }
      
      res.json(payouts);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch payouts" });
    }
  });

  app.patch("/api/admin/payouts/:id", async (req, res) => {
    try {
      const updates = req.body;
      const payout = await storage.updatePayout(req.params.id, updates);
      if (!payout) {
        return res.status(404).json({ message: "Payout not found" });
      }
      res.json(payout);
    } catch (error) {
      res.status(500).json({ message: "Failed to update payout" });
    }
  });

  app.post("/api/admin/payouts", async (req, res) => {
    try {
      const validatedData = insertPayoutSchema.parse(req.body);
      const payout = await storage.createPayout(validatedData);
      res.status(201).json(payout);
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ message: "Invalid payout data", errors: error.errors });
      }
      res.status(500).json({ message: "Failed to create payout" });
    }
  });

  app.post("/api/admin/payouts/process-all", async (req, res) => {
    try {
      const pendingPayouts = await storage.getPayoutsByStatus("pending");
      const processed = [];
      
      for (const payout of pendingPayouts) {
        const updated = await storage.updatePayout(payout.id, { status: "processing" });
        if (updated) processed.push(updated);
      }
      
      res.json({ processed: processed.length, payouts: processed });
    } catch (error) {
      res.status(500).json({ message: "Failed to process payouts" });
    }
  });

  // Platform settings
  app.get("/api/admin/settings", async (req, res) => {
    try {
      const settings = await storage.getPlatformSettings();
      res.json(settings);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch settings" });
    }
  });

  app.patch("/api/admin/settings", async (req, res) => {
    try {
      const validatedData = insertPlatformSettingsSchema.parse(req.body);
      const settings = await storage.updatePlatformSettings(validatedData);
      res.json(settings);
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ message: "Invalid settings data", errors: error.errors });
      }
      res.status(500).json({ message: "Failed to update settings" });
    }
  });

  const httpServer = createServer(app);
  return httpServer;
}
