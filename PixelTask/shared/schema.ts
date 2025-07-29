import { sql, relations } from "drizzle-orm";
import { pgTable, text, varchar, decimal, integer, timestamp, boolean, pgEnum } from "drizzle-orm/pg-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod";

export const userRoleEnum = pgEnum("user_role", ["admin", "client", "worker"]);
export const taskStatusEnum = pgEnum("task_status", ["active", "completed", "cancelled"]);
export const submissionStatusEnum = pgEnum("submission_status", ["pending", "approved", "rejected"]);
export const withdrawalStatusEnum = pgEnum("withdrawal_status", ["pending", "processing", "completed", "rejected"]);
export const paymentMethodEnum = pgEnum("payment_method", ["jazzcash", "easypaisa", "paytm", "usdt"]);

export const users = pgTable("users", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  name: text("name").notNull(),
  email: text("email").notNull().unique(),
  role: userRoleEnum("role").notNull(),
  accessKey: text("access_key").notNull().unique(),
  walletBalance: decimal("wallet_balance", { precision: 10, scale: 2 }).default("0.00"),
  skills: text("skills").array(),
  companyName: text("company_name"),
  createdAt: timestamp("created_at").defaultNow()
});

export const tasks = pgTable("tasks", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  title: text("title").notNull(),
  description: text("description").notNull(),
  category: text("category").notNull(),
  price: decimal("price", { precision: 10, scale: 2 }).notNull(),
  estimatedTime: text("estimated_time"),
  spotsAvailable: integer("spots_available").notNull().default(1),
  status: taskStatusEnum("status").notNull().default("active"),
  clientId: varchar("client_id").notNull().references(() => users.id),
  createdAt: timestamp("created_at").defaultNow()
});

export const submissions = pgTable("submissions", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  taskId: varchar("task_id").notNull().references(() => tasks.id),
  workerId: varchar("worker_id").notNull().references(() => users.id),
  proofText: text("proof_text"),
  proofFileUrl: text("proof_file_url"),
  status: submissionStatusEnum("status").notNull().default("pending"),
  submittedAt: timestamp("submitted_at").defaultNow(),
  reviewedAt: timestamp("reviewed_at"),
  adminNotes: text("admin_notes")
});

export const withdrawals = pgTable("withdrawals", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  userId: varchar("user_id").notNull().references(() => users.id),
  amount: decimal("amount", { precision: 10, scale: 2 }).notNull(),
  paymentMethod: paymentMethodEnum("payment_method").notNull(),
  paymentDetails: text("payment_details").notNull(),
  status: withdrawalStatusEnum("status").notNull().default("pending"),
  requestedAt: timestamp("requested_at").defaultNow(),
  processedAt: timestamp("processed_at"),
  adminNotes: text("admin_notes")
});

export const transactions = pgTable("transactions", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  userId: varchar("user_id").notNull().references(() => users.id),
  amount: decimal("amount", { precision: 10, scale: 2 }).notNull(),
  type: text("type").notNull(), // 'earning', 'withdrawal', 'fee'
  description: text("description").notNull(),
  taskId: varchar("task_id").references(() => tasks.id),
  submissionId: varchar("submission_id").references(() => submissions.id),
  createdAt: timestamp("created_at").defaultNow()
});

// Relations
export const usersRelations = relations(users, ({ many }) => ({
  tasks: many(tasks),
  submissions: many(submissions),
  withdrawals: many(withdrawals),
  transactions: many(transactions)
}));

export const tasksRelations = relations(tasks, ({ one, many }) => ({
  client: one(users, {
    fields: [tasks.clientId],
    references: [users.id]
  }),
  submissions: many(submissions)
}));

export const submissionsRelations = relations(submissions, ({ one }) => ({
  task: one(tasks, {
    fields: [submissions.taskId],
    references: [tasks.id]
  }),
  worker: one(users, {
    fields: [submissions.workerId],
    references: [users.id]
  })
}));

export const withdrawalsRelations = relations(withdrawals, ({ one }) => ({
  user: one(users, {
    fields: [withdrawals.userId],
    references: [users.id]
  })
}));

export const transactionsRelations = relations(transactions, ({ one }) => ({
  user: one(users, {
    fields: [transactions.userId],
    references: [users.id]
  }),
  task: one(tasks, {
    fields: [transactions.taskId],
    references: [tasks.id]
  }),
  submission: one(submissions, {
    fields: [transactions.submissionId],
    references: [submissions.id]
  })
}));

// Insert schemas
export const insertUserSchema = createInsertSchema(users).omit({
  id: true,
  createdAt: true,
  walletBalance: true
});

export const insertTaskSchema = createInsertSchema(tasks).omit({
  id: true,
  createdAt: true,
  status: true
}).extend({
  price: z.string().refine((val) => parseFloat(val) >= 0.02, {
    message: "Minimum task value is $0.02"
  })
});

export const insertSubmissionSchema = createInsertSchema(submissions).omit({
  id: true,
  submittedAt: true,
  reviewedAt: true,
  status: true,
  adminNotes: true
});

export const insertWithdrawalSchema = createInsertSchema(withdrawals).omit({
  id: true,
  requestedAt: true,
  processedAt: true,
  status: true,
  adminNotes: true
}).extend({
  amount: z.string().refine((val) => parseFloat(val) >= 3.00, {
    message: "Minimum withdrawal amount is $3.00"
  })
});

// Types
export type User = typeof users.$inferSelect;
export type InsertUser = z.infer<typeof insertUserSchema>;
export type Task = typeof tasks.$inferSelect;
export type InsertTask = z.infer<typeof insertTaskSchema>;
export type Submission = typeof submissions.$inferSelect;
export type InsertSubmission = z.infer<typeof insertSubmissionSchema>;
export type Withdrawal = typeof withdrawals.$inferSelect;
export type InsertWithdrawal = z.infer<typeof insertWithdrawalSchema>;
export type Transaction = typeof transactions.$inferSelect;
