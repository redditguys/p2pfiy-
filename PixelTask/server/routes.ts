import type { Express } from "express";
import { createServer, type Server } from "http";
import { storage } from "./storage";
import { insertUserSchema, insertTaskSchema, insertSubmissionSchema, insertWithdrawalSchema } from "@shared/schema";
import { z } from "zod";

export async function registerRoutes(app: Express): Promise<Server> {
  // Authentication routes
  app.post("/api/auth/login", async (req, res) => {
    try {
      const { accessKey, name, email, role, companyName, skills } = req.body;
      
      if (accessKey === "nafisabat103@FR") {
        // Admin access
        return res.json({ 
          user: { 
            id: "admin", 
            role: "admin", 
            name: "Admin", 
            email: "admin@p2pfiy.com",
            accessKey 
          } 
        });
      }

      let user = await storage.getUserByAccessKey(accessKey);
      
      if (!user && role) {
        // Create new user if not found and registration data provided
        const userData = insertUserSchema.parse({
          accessKey,
          name,
          email,
          role,
          companyName: role === 'client' ? companyName : undefined,
          skills: role === 'worker' ? skills : undefined
        });
        user = await storage.createUser(userData);
      }

      if (!user) {
        return res.status(401).json({ message: "Invalid access key" });
      }

      res.json({ user });
    } catch (error) {
      res.status(400).json({ message: error instanceof Error ? error.message : "Authentication failed" });
    }
  });

  // Task routes
  app.get("/api/tasks", async (req, res) => {
    try {
      const tasks = await storage.getAllTasks();
      res.json(tasks);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch tasks" });
    }
  });

  app.get("/api/tasks/client/:clientId", async (req, res) => {
    try {
      const tasks = await storage.getTasksByClient(req.params.clientId);
      res.json(tasks);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch client tasks" });
    }
  });

  app.post("/api/tasks", async (req, res) => {
    try {
      const taskData = insertTaskSchema.parse(req.body);
      const task = await storage.createTask(taskData);
      res.json(task);
    } catch (error) {
      res.status(400).json({ message: error instanceof Error ? error.message : "Failed to create task" });
    }
  });

  // Submission routes
  app.get("/api/submissions/worker/:workerId", async (req, res) => {
    try {
      const submissions = await storage.getSubmissionsByWorker(req.params.workerId);
      res.json(submissions);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch submissions" });
    }
  });

  app.get("/api/submissions/pending", async (req, res) => {
    try {
      const submissions = await storage.getPendingSubmissions();
      res.json(submissions);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch pending submissions" });
    }
  });

  app.post("/api/submissions", async (req, res) => {
    try {
      const submissionData = insertSubmissionSchema.parse(req.body);
      const submission = await storage.createSubmission(submissionData);
      
      // Update task spots
      await storage.updateTaskSpots(submissionData.taskId, 1);
      
      res.json(submission);
    } catch (error) {
      res.status(400).json({ message: error instanceof Error ? error.message : "Failed to create submission" });
    }
  });

  app.patch("/api/submissions/:id/review", async (req, res) => {
    try {
      const { status, adminNotes } = req.body;
      const submissionId = req.params.id;
      
      if (!['approved', 'rejected'].includes(status)) {
        return res.status(400).json({ message: "Invalid status" });
      }

      await storage.updateSubmissionStatus(submissionId, status, adminNotes);
      
      if (status === 'approved') {
        // Get submission details to process payment
        const submission = await storage.getSubmission(submissionId);
        if (submission) {
          const task = await storage.getTask(submission.taskId);
          if (task) {
            // Add earnings to worker wallet
            await storage.updateUserWallet(submission.workerId, parseFloat(task.price));
            
            // Create transaction record
            await storage.createTransaction({
              userId: submission.workerId,
              amount: task.price,
              type: 'earning',
              description: `Payment for task: ${task.title}`,
              taskId: task.id,
              submissionId: submission.id
            });
          }
        }
      }
      
      res.json({ success: true });
    } catch (error) {
      res.status(400).json({ message: error instanceof Error ? error.message : "Failed to review submission" });
    }
  });

  // Withdrawal routes
  app.get("/api/withdrawals/pending", async (req, res) => {
    try {
      const withdrawals = await storage.getPendingWithdrawals();
      res.json(withdrawals);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch pending withdrawals" });
    }
  });

  app.get("/api/withdrawals/user/:userId", async (req, res) => {
    try {
      const withdrawals = await storage.getWithdrawalsByUser(req.params.userId);
      res.json(withdrawals);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch user withdrawals" });
    }
  });

  app.post("/api/withdrawals", async (req, res) => {
    try {
      const withdrawalData = insertWithdrawalSchema.parse(req.body);
      
      // Check user balance
      const user = await storage.getUser(withdrawalData.userId);
      if (!user || parseFloat(user.walletBalance || "0") < parseFloat(withdrawalData.amount)) {
        return res.status(400).json({ message: "Insufficient balance" });
      }
      
      const withdrawal = await storage.createWithdrawal(withdrawalData);
      
      // Deduct from wallet
      await storage.updateUserWallet(withdrawalData.userId, -parseFloat(withdrawalData.amount));
      
      // Create transaction record
      await storage.createTransaction({
        userId: withdrawalData.userId,
        amount: `-${withdrawalData.amount}`,
        type: 'withdrawal',
        description: `Withdrawal request via ${withdrawalData.paymentMethod}`,
        taskId: null,
        submissionId: null
      });
      
      res.json(withdrawal);
    } catch (error) {
      res.status(400).json({ message: error instanceof Error ? error.message : "Failed to create withdrawal" });
    }
  });

  app.patch("/api/withdrawals/:id/process", async (req, res) => {
    try {
      const { status, adminNotes } = req.body;
      const withdrawalId = req.params.id;
      
      if (!['processing', 'completed', 'rejected'].includes(status)) {
        return res.status(400).json({ message: "Invalid status" });
      }

      await storage.updateWithdrawalStatus(withdrawalId, status, adminNotes);
      
      if (status === 'rejected') {
        // Refund to wallet if rejected
        const withdrawal = await storage.getWithdrawalsByUser(''); // This needs the withdrawal object
        // Implementation would need adjustment to get withdrawal details
      }
      
      res.json({ success: true });
    } catch (error) {
      res.status(400).json({ message: error instanceof Error ? error.message : "Failed to process withdrawal" });
    }
  });

  // Transaction routes
  app.get("/api/transactions/user/:userId", async (req, res) => {
    try {
      const transactions = await storage.getTransactionsByUser(req.params.userId);
      res.json(transactions);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch transactions" });
    }
  });

  // Admin stats
  app.get("/api/admin/stats", async (req, res) => {
    try {
      const stats = await storage.getAdminStats();
      res.json(stats);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch admin stats" });
    }
  });

  // User route
  app.get("/api/users/:id", async (req, res) => {
    try {
      const user = await storage.getUser(req.params.id);
      if (!user) {
        return res.status(404).json({ message: "User not found" });
      }
      res.json(user);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch user" });
    }
  });

  const httpServer = createServer(app);
  return httpServer;
}
