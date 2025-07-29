import { 
  type User, 
  type InsertUser, 
  type Transaction, 
  type InsertTransaction,
  type Dispute,
  type InsertDispute,
  type Payout,
  type InsertPayout,
  type PlatformSettings,
  type InsertPlatformSettings,
  type TransactionWithUsers,
  type DisputeWithTransaction,
  type PayoutWithWorker
} from "@shared/schema";
import { randomUUID } from "crypto";
import bcrypt from "bcrypt";

export interface IStorage {
  // User management
  getUser(id: string): Promise<User | undefined>;
  getUserByUsername(username: string): Promise<User | undefined>;
  getUserByEmail(email: string): Promise<User | undefined>;
  createUser(user: InsertUser): Promise<User>;
  updateUser(id: string, updates: Partial<User>): Promise<User | undefined>;
  getAllUsers(): Promise<User[]>;
  getUsersByRole(role: string): Promise<User[]>;

  // Transaction management
  getTransaction(id: string): Promise<Transaction | undefined>;
  getTransactionWithUsers(id: string): Promise<TransactionWithUsers | undefined>;
  createTransaction(transaction: InsertTransaction): Promise<Transaction>;
  updateTransaction(id: string, updates: Partial<Transaction>): Promise<Transaction | undefined>;
  getAllTransactions(): Promise<TransactionWithUsers[]>;
  getTransactionsByStatus(status: string): Promise<TransactionWithUsers[]>;
  getTransactionsByUser(userId: string): Promise<TransactionWithUsers[]>;

  // Dispute management
  getDispute(id: string): Promise<Dispute | undefined>;
  getDisputeWithTransaction(id: string): Promise<DisputeWithTransaction | undefined>;
  createDispute(dispute: InsertDispute): Promise<Dispute>;
  updateDispute(id: string, updates: Partial<Dispute>): Promise<Dispute | undefined>;
  getAllDisputes(): Promise<DisputeWithTransaction[]>;
  getDisputesByStatus(status: string): Promise<DisputeWithTransaction[]>;

  // Payout management
  getPayout(id: string): Promise<Payout | undefined>;
  getPayoutWithWorker(id: string): Promise<PayoutWithWorker | undefined>;
  createPayout(payout: InsertPayout): Promise<Payout>;
  updatePayout(id: string, updates: Partial<Payout>): Promise<Payout | undefined>;
  getAllPayouts(): Promise<PayoutWithWorker[]>;
  getPayoutsByStatus(status: string): Promise<PayoutWithWorker[]>;
  getPayoutsByWorker(workerId: string): Promise<PayoutWithWorker[]>;

  // Platform settings
  getPlatformSettings(): Promise<PlatformSettings | undefined>;
  updatePlatformSettings(settings: InsertPlatformSettings): Promise<PlatformSettings>;

  // Admin authentication
  authenticateAdmin(email: string, password: string): Promise<User | null>;
}

export class MemStorage implements IStorage {
  private users: Map<string, User>;
  private transactions: Map<string, Transaction>;
  private disputes: Map<string, Dispute>;
  private payouts: Map<string, Payout>;
  private platformSettings: PlatformSettings | undefined;

  constructor() {
    this.users = new Map();
    this.transactions = new Map();
    this.disputes = new Map();
    this.payouts = new Map();
    this.initializeData();
  }

  private async initializeData() {
    // Create admin user
    const adminPassword = await bcrypt.hash("aass1122@FRP@", 10);
    const adminUser: User = {
      id: randomUUID(),
      username: "admin",
      email: "mathfun103@gmail.com",
      password: adminPassword,
      role: "admin",
      createdAt: new Date(),
      isActive: true,
      profileVerified: true,
    };
    this.users.set(adminUser.id, adminUser);

    // Initialize platform settings
    this.platformSettings = {
      id: randomUUID(),
      commissionRate: "5.00",
      processingFee: "0.30",
      payoutSchedule: "weekly",
      updatedAt: new Date(),
    };

    // Create some sample users for testing
    const clientPassword = await bcrypt.hash("password123", 10);
    const workerPassword = await bcrypt.hash("password123", 10);

    const clients = [
      { username: "sarah_chen", email: "sarah.chen@email.com", role: "client" as const },
      { username: "david_kim", email: "david.kim@startup.com", role: "client" as const },
      { username: "lisa_johnson", email: "lisa@agency.com", role: "client" as const },
    ];

    const workers = [
      { username: "mike_rodriguez", email: "mike.rodriguez@freelancer.com", role: "worker" as const },
      { username: "emma_wilson", email: "emma.wilson@designer.com", role: "worker" as const },
      { username: "alex_thompson", email: "alex.thompson@writer.com", role: "worker" as const },
    ];

    for (const client of clients) {
      const user: User = {
        id: randomUUID(),
        ...client,
        password: clientPassword,
        createdAt: new Date(),
        isActive: true,
        profileVerified: true,
      };
      this.users.set(user.id, user);
    }

    for (const worker of workers) {
      const user: User = {
        id: randomUUID(),
        ...worker,
        password: workerPassword,
        createdAt: new Date(),
        isActive: true,
        profileVerified: true,
      };
      this.users.set(user.id, user);
    }
  }

  // User management
  async getUser(id: string): Promise<User | undefined> {
    return this.users.get(id);
  }

  async getUserByUsername(username: string): Promise<User | undefined> {
    return Array.from(this.users.values()).find(user => user.username === username);
  }

  async getUserByEmail(email: string): Promise<User | undefined> {
    return Array.from(this.users.values()).find(user => user.email === email);
  }

  async createUser(insertUser: InsertUser): Promise<User> {
    const id = randomUUID();
    const hashedPassword = await bcrypt.hash(insertUser.password, 10);
    const user: User = { 
      ...insertUser, 
      id, 
      password: hashedPassword,
      createdAt: new Date(),
      isActive: true,
      profileVerified: false,
    };
    this.users.set(id, user);
    return user;
  }

  async updateUser(id: string, updates: Partial<User>): Promise<User | undefined> {
    const user = this.users.get(id);
    if (!user) return undefined;
    
    const updatedUser = { ...user, ...updates };
    this.users.set(id, updatedUser);
    return updatedUser;
  }

  async getAllUsers(): Promise<User[]> {
    return Array.from(this.users.values());
  }

  async getUsersByRole(role: string): Promise<User[]> {
    return Array.from(this.users.values()).filter(user => user.role === role);
  }

  // Transaction management
  async getTransaction(id: string): Promise<Transaction | undefined> {
    return this.transactions.get(id);
  }

  async getTransactionWithUsers(id: string): Promise<TransactionWithUsers | undefined> {
    const transaction = this.transactions.get(id);
    if (!transaction) return undefined;

    const client = this.users.get(transaction.clientId);
    const worker = this.users.get(transaction.workerId);
    
    if (!client || !worker) return undefined;

    return { ...transaction, client, worker };
  }

  async createTransaction(insertTransaction: InsertTransaction): Promise<Transaction> {
    const id = randomUUID();
    const transaction: Transaction = { 
      ...insertTransaction, 
      id,
      createdAt: new Date(),
      completedAt: null,
    };
    this.transactions.set(id, transaction);
    return transaction;
  }

  async updateTransaction(id: string, updates: Partial<Transaction>): Promise<Transaction | undefined> {
    const transaction = this.transactions.get(id);
    if (!transaction) return undefined;
    
    const updatedTransaction = { ...transaction, ...updates };
    if (updates.status === "completed" && !transaction.completedAt) {
      updatedTransaction.completedAt = new Date();
    }
    
    this.transactions.set(id, updatedTransaction);
    return updatedTransaction;
  }

  async getAllTransactions(): Promise<TransactionWithUsers[]> {
    const transactions = Array.from(this.transactions.values());
    const result: TransactionWithUsers[] = [];
    
    for (const transaction of transactions) {
      const client = this.users.get(transaction.clientId);
      const worker = this.users.get(transaction.workerId);
      
      if (client && worker) {
        result.push({ ...transaction, client, worker });
      }
    }
    
    return result.sort((a, b) => new Date(b.createdAt!).getTime() - new Date(a.createdAt!).getTime());
  }

  async getTransactionsByStatus(status: string): Promise<TransactionWithUsers[]> {
    const allTransactions = await this.getAllTransactions();
    return allTransactions.filter(transaction => transaction.status === status);
  }

  async getTransactionsByUser(userId: string): Promise<TransactionWithUsers[]> {
    const allTransactions = await this.getAllTransactions();
    return allTransactions.filter(transaction => 
      transaction.clientId === userId || transaction.workerId === userId
    );
  }

  // Dispute management
  async getDispute(id: string): Promise<Dispute | undefined> {
    return this.disputes.get(id);
  }

  async getDisputeWithTransaction(id: string): Promise<DisputeWithTransaction | undefined> {
    const dispute = this.disputes.get(id);
    if (!dispute) return undefined;

    const transactionWithUsers = await this.getTransactionWithUsers(dispute.transactionId);
    const reporter = this.users.get(dispute.reporterId);
    
    if (!transactionWithUsers || !reporter) return undefined;

    return { ...dispute, transaction: transactionWithUsers, reporter };
  }

  async createDispute(insertDispute: InsertDispute): Promise<Dispute> {
    const id = randomUUID();
    const dispute: Dispute = { 
      ...insertDispute, 
      id,
      createdAt: new Date(),
      resolvedAt: null,
      resolution: null,
    };
    this.disputes.set(id, dispute);
    return dispute;
  }

  async updateDispute(id: string, updates: Partial<Dispute>): Promise<Dispute | undefined> {
    const dispute = this.disputes.get(id);
    if (!dispute) return undefined;
    
    const updatedDispute = { ...dispute, ...updates };
    if (updates.status === "resolved" && !dispute.resolvedAt) {
      updatedDispute.resolvedAt = new Date();
    }
    
    this.disputes.set(id, updatedDispute);
    return updatedDispute;
  }

  async getAllDisputes(): Promise<DisputeWithTransaction[]> {
    const disputes = Array.from(this.disputes.values());
    const result: DisputeWithTransaction[] = [];
    
    for (const dispute of disputes) {
      const transactionWithUsers = await this.getTransactionWithUsers(dispute.transactionId);
      const reporter = this.users.get(dispute.reporterId);
      
      if (transactionWithUsers && reporter) {
        result.push({ ...dispute, transaction: transactionWithUsers, reporter });
      }
    }
    
    return result.sort((a, b) => new Date(b.createdAt!).getTime() - new Date(a.createdAt!).getTime());
  }

  async getDisputesByStatus(status: string): Promise<DisputeWithTransaction[]> {
    const allDisputes = await this.getAllDisputes();
    return allDisputes.filter(dispute => dispute.status === status);
  }

  // Payout management
  async getPayout(id: string): Promise<Payout | undefined> {
    return this.payouts.get(id);
  }

  async getPayoutWithWorker(id: string): Promise<PayoutWithWorker | undefined> {
    const payout = this.payouts.get(id);
    if (!payout) return undefined;

    const worker = this.users.get(payout.workerId);
    if (!worker) return undefined;

    return { ...payout, worker };
  }

  async createPayout(insertPayout: InsertPayout): Promise<Payout> {
    const id = randomUUID();
    const payout: Payout = { 
      ...insertPayout, 
      id,
      createdAt: new Date(),
      processedAt: null,
      failureReason: null,
    };
    this.payouts.set(id, payout);
    return payout;
  }

  async updatePayout(id: string, updates: Partial<Payout>): Promise<Payout | undefined> {
    const payout = this.payouts.get(id);
    if (!payout) return undefined;
    
    const updatedPayout = { ...payout, ...updates };
    if ((updates.status === "completed" || updates.status === "failed") && !payout.processedAt) {
      updatedPayout.processedAt = new Date();
    }
    
    this.payouts.set(id, updatedPayout);
    return updatedPayout;
  }

  async getAllPayouts(): Promise<PayoutWithWorker[]> {
    const payouts = Array.from(this.payouts.values());
    const result: PayoutWithWorker[] = [];
    
    for (const payout of payouts) {
      const worker = this.users.get(payout.workerId);
      
      if (worker) {
        result.push({ ...payout, worker });
      }
    }
    
    return result.sort((a, b) => new Date(b.createdAt!).getTime() - new Date(a.createdAt!).getTime());
  }

  async getPayoutsByStatus(status: string): Promise<PayoutWithWorker[]> {
    const allPayouts = await this.getAllPayouts();
    return allPayouts.filter(payout => payout.status === status);
  }

  async getPayoutsByWorker(workerId: string): Promise<PayoutWithWorker[]> {
    const allPayouts = await this.getAllPayouts();
    return allPayouts.filter(payout => payout.workerId === workerId);
  }

  // Platform settings
  async getPlatformSettings(): Promise<PlatformSettings | undefined> {
    return this.platformSettings;
  }

  async updatePlatformSettings(settings: InsertPlatformSettings): Promise<PlatformSettings> {
    this.platformSettings = {
      id: this.platformSettings?.id || randomUUID(),
      ...settings,
      updatedAt: new Date(),
    };
    return this.platformSettings;
  }

  // Admin authentication
  async authenticateAdmin(email: string, password: string): Promise<User | null> {
    const user = await this.getUserByEmail(email);
    if (!user || user.role !== "admin") return null;
    
    const isValidPassword = await bcrypt.compare(password, user.password);
    return isValidPassword ? user : null;
  }
}

export const storage = new MemStorage();
