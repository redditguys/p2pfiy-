import { 
  users, tasks, submissions, withdrawals, transactions,
  type User, type InsertUser, type Task, type InsertTask,
  type Submission, type InsertSubmission, type Withdrawal, type InsertWithdrawal,
  type Transaction
} from "@shared/schema";
import { db } from "./db";
import { eq, desc, and, sql } from "drizzle-orm";

export interface IStorage {
  // User operations
  getUser(id: string): Promise<User | undefined>;
  getUserByAccessKey(accessKey: string): Promise<User | undefined>;
  getUserByEmail(email: string): Promise<User | undefined>;
  createUser(user: InsertUser): Promise<User>;
  updateUserWallet(userId: string, amount: number): Promise<void>;

  // Task operations
  getAllTasks(): Promise<Task[]>;
  getTasksByClient(clientId: string): Promise<Task[]>;
  getTask(id: string): Promise<Task | undefined>;
  createTask(task: InsertTask): Promise<Task>;
  updateTaskSpots(taskId: string, spotsUsed: number): Promise<void>;

  // Submission operations
  getSubmissionsByTask(taskId: string): Promise<Submission[]>;
  getSubmissionsByWorker(workerId: string): Promise<Submission[]>;
  getPendingSubmissions(): Promise<Array<Submission & { task: Task; worker: User }>>;
  getSubmission(id: string): Promise<Submission | undefined>;
  createSubmission(submission: InsertSubmission): Promise<Submission>;
  updateSubmissionStatus(id: string, status: 'approved' | 'rejected', adminNotes?: string): Promise<void>;

  // Withdrawal operations
  getPendingWithdrawals(): Promise<Array<Withdrawal & { user: User }>>;
  getWithdrawalsByUser(userId: string): Promise<Withdrawal[]>;
  createWithdrawal(withdrawal: InsertWithdrawal): Promise<Withdrawal>;
  updateWithdrawalStatus(id: string, status: 'processing' | 'completed' | 'rejected', adminNotes?: string): Promise<void>;

  // Transaction operations
  getTransactionsByUser(userId: string): Promise<Transaction[]>;
  createTransaction(transaction: Omit<Transaction, 'id' | 'createdAt'>): Promise<Transaction>;

  // Stats
  getAdminStats(): Promise<{
    totalTasks: number;
    activeWorkers: number;
    activeClients: number;
    totalEarnings: string;
  }>;
}

export class DatabaseStorage implements IStorage {
  async getUser(id: string): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.id, id));
    return user || undefined;
  }

  async getUserByAccessKey(accessKey: string): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.accessKey, accessKey));
    return user || undefined;
  }

  async getUserByEmail(email: string): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.email, email));
    return user || undefined;
  }

  async createUser(insertUser: InsertUser): Promise<User> {
    const [user] = await db
      .insert(users)
      .values(insertUser)
      .returning();
    return user;
  }

  async updateUserWallet(userId: string, amount: number): Promise<void> {
    await db
      .update(users)
      .set({ 
        walletBalance: sql`${users.walletBalance} + ${amount}`
      })
      .where(eq(users.id, userId));
  }

  async getAllTasks(): Promise<Task[]> {
    return await db
      .select()
      .from(tasks)
      .where(eq(tasks.status, 'active'))
      .orderBy(desc(tasks.createdAt));
  }

  async getTasksByClient(clientId: string): Promise<Task[]> {
    return await db
      .select()
      .from(tasks)
      .where(eq(tasks.clientId, clientId))
      .orderBy(desc(tasks.createdAt));
  }

  async getTask(id: string): Promise<Task | undefined> {
    const [task] = await db.select().from(tasks).where(eq(tasks.id, id));
    return task || undefined;
  }

  async createTask(insertTask: InsertTask): Promise<Task> {
    const [task] = await db
      .insert(tasks)
      .values(insertTask)
      .returning();
    return task;
  }

  async updateTaskSpots(taskId: string, spotsUsed: number): Promise<void> {
    await db
      .update(tasks)
      .set({ 
        spotsAvailable: sql`${tasks.spotsAvailable} - ${spotsUsed}`
      })
      .where(eq(tasks.id, taskId));
  }

  async getSubmissionsByTask(taskId: string): Promise<Submission[]> {
    return await db
      .select()
      .from(submissions)
      .where(eq(submissions.taskId, taskId))
      .orderBy(desc(submissions.submittedAt));
  }

  async getSubmissionsByWorker(workerId: string): Promise<Submission[]> {
    return await db
      .select()
      .from(submissions)
      .where(eq(submissions.workerId, workerId))
      .orderBy(desc(submissions.submittedAt));
  }

  async getPendingSubmissions(): Promise<Array<Submission & { task: Task; worker: User }>> {
    return await db
      .select({
        id: submissions.id,
        taskId: submissions.taskId,
        workerId: submissions.workerId,
        proofText: submissions.proofText,
        proofFileUrl: submissions.proofFileUrl,
        status: submissions.status,
        submittedAt: submissions.submittedAt,
        reviewedAt: submissions.reviewedAt,
        adminNotes: submissions.adminNotes,
        task: tasks,
        worker: users
      })
      .from(submissions)
      .innerJoin(tasks, eq(submissions.taskId, tasks.id))
      .innerJoin(users, eq(submissions.workerId, users.id))
      .where(eq(submissions.status, 'pending'))
      .orderBy(desc(submissions.submittedAt));
  }

  async getSubmission(id: string): Promise<Submission | undefined> {
    const [submission] = await db.select().from(submissions).where(eq(submissions.id, id));
    return submission || undefined;
  }

  async createSubmission(insertSubmission: InsertSubmission): Promise<Submission> {
    const [submission] = await db
      .insert(submissions)
      .values(insertSubmission)
      .returning();
    return submission;
  }

  async updateSubmissionStatus(id: string, status: 'approved' | 'rejected', adminNotes?: string): Promise<void> {
    await db
      .update(submissions)
      .set({ 
        status,
        adminNotes,
        reviewedAt: new Date()
      })
      .where(eq(submissions.id, id));
  }

  async getPendingWithdrawals(): Promise<Array<Withdrawal & { user: User }>> {
    return await db
      .select({
        id: withdrawals.id,
        userId: withdrawals.userId,
        amount: withdrawals.amount,
        paymentMethod: withdrawals.paymentMethod,
        paymentDetails: withdrawals.paymentDetails,
        status: withdrawals.status,
        requestedAt: withdrawals.requestedAt,
        processedAt: withdrawals.processedAt,
        adminNotes: withdrawals.adminNotes,
        user: users
      })
      .from(withdrawals)
      .innerJoin(users, eq(withdrawals.userId, users.id))
      .where(eq(withdrawals.status, 'pending'))
      .orderBy(desc(withdrawals.requestedAt));
  }

  async getWithdrawalsByUser(userId: string): Promise<Withdrawal[]> {
    return await db
      .select()
      .from(withdrawals)
      .where(eq(withdrawals.userId, userId))
      .orderBy(desc(withdrawals.requestedAt));
  }

  async createWithdrawal(insertWithdrawal: InsertWithdrawal): Promise<Withdrawal> {
    const [withdrawal] = await db
      .insert(withdrawals)
      .values(insertWithdrawal)
      .returning();
    return withdrawal;
  }

  async updateWithdrawalStatus(id: string, status: 'processing' | 'completed' | 'rejected', adminNotes?: string): Promise<void> {
    await db
      .update(withdrawals)
      .set({ 
        status,
        adminNotes,
        processedAt: new Date()
      })
      .where(eq(withdrawals.id, id));
  }

  async getTransactionsByUser(userId: string): Promise<Transaction[]> {
    return await db
      .select()
      .from(transactions)
      .where(eq(transactions.userId, userId))
      .orderBy(desc(transactions.createdAt));
  }

  async createTransaction(transaction: Omit<Transaction, 'id' | 'createdAt'>): Promise<Transaction> {
    const [newTransaction] = await db
      .insert(transactions)
      .values(transaction)
      .returning();
    return newTransaction;
  }

  async getAdminStats(): Promise<{
    totalTasks: number;
    activeWorkers: number;
    activeClients: number;
    totalEarnings: string;
  }> {
    const [taskCount] = await db
      .select({ count: sql<number>`count(*)` })
      .from(tasks);

    const [workerCount] = await db
      .select({ count: sql<number>`count(*)` })
      .from(users)
      .where(eq(users.role, 'worker'));

    const [clientCount] = await db
      .select({ count: sql<number>`count(*)` })
      .from(users)
      .where(eq(users.role, 'client'));

    const [earningsResult] = await db
      .select({ total: sql<string>`COALESCE(SUM(${transactions.amount}), 0)` })
      .from(transactions)
      .where(eq(transactions.type, 'earning'));

    return {
      totalTasks: taskCount.count,
      activeWorkers: workerCount.count,
      activeClients: clientCount.count,
      totalEarnings: earningsResult.total
    };
  }
}

export const storage = new DatabaseStorage();
