# P2PFIY - Micro-Task Platform

## Overview

P2PFIY is a full-stack micro-task platform that connects clients who need small tasks completed with workers who can perform them. The platform features a Minecraft-themed UI and supports three user roles: admin, client, and worker. 

**Current Status**: Complete PHP/MySQL version created in `php-version/` directory with Bootstrap 5 dark theme. The original React/Node.js/PostgreSQL version remains in the main directory for reference.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Frontend Architecture
- **Framework**: React 18 with TypeScript
- **Routing**: Wouter for client-side routing
- **State Management**: TanStack Query for server state management
- **UI Components**: Radix UI primitives with shadcn/ui component library
- **Styling**: Tailwind CSS with custom Minecraft-themed color palette
- **Build Tool**: Vite for development and production builds

### Backend Architecture

**Original React/Node.js Version:**
- **Framework**: Express.js with TypeScript
- **Database**: PostgreSQL with Drizzle ORM
- **Database Provider**: Neon Database (@neondatabase/serverless)
- **Session Management**: Built-in authentication system with access keys
- **API Structure**: RESTful endpoints under `/api` prefix

**New PHP/MySQL Version:**
- **Framework**: Plain PHP with procedural and OOP patterns
- **Database**: MySQL with PDO for secure database operations
- **UI Framework**: Bootstrap 5 with dark theme
- **Session Management**: PHP sessions with key-based authentication
- **File Structure**: Traditional server-side rendering with modular includes

### Database Schema
The application uses PostgreSQL with the following main entities:
- **Users**: Stores user information with roles (admin, client, worker)
- **Tasks**: Manages task listings created by clients
- **Submissions**: Tracks worker submissions for tasks
- **Withdrawals**: Handles worker withdrawal requests
- **Transactions**: Records financial transactions

## Key Components

### Authentication System
- Access key-based authentication (no traditional passwords)
- Role-based access control (admin, client, worker)
- Special admin access key: "nafisabat103@FR"
- Client-side auth state management with custom AuthManager

### Task Management
- Clients can create tasks with categories, pricing, and spot limits
- Workers can browse and apply to available tasks
- Submission system with proof text/file upload support
- Admin review and approval workflow

### Financial System
- Virtual wallet system for users
- Withdrawal requests with multiple payment methods (JazzCash, EasyPaisa, PayTM, USDT)
- Transaction tracking and history
- Admin-controlled payment processing

### UI/UX Features
- Minecraft-themed design with pixel-perfect styling
- Responsive design for desktop and mobile
- Modal-based workflows for task creation and submissions
- Real-time updates using TanStack Query
- Toast notifications for user feedback

## Data Flow

1. **User Registration/Login**: Users authenticate using access keys, with automatic user creation for new keys
2. **Task Creation**: Clients create tasks through modal forms, stored in PostgreSQL
3. **Task Discovery**: Workers browse tasks with filtering and search capabilities
4. **Task Submission**: Workers submit proof of completion through submission forms
5. **Admin Review**: Admins review submissions and approve/reject them
6. **Payment Processing**: Approved submissions trigger wallet updates and withdrawal flows

## External Dependencies

### Core Dependencies
- **@neondatabase/serverless**: PostgreSQL database connectivity
- **drizzle-orm**: Type-safe database ORM
- **@tanstack/react-query**: Server state management
- **@radix-ui/***: Headless UI component primitives
- **tailwindcss**: Utility-first CSS framework
- **wouter**: Lightweight React router

### Development Tools
- **vite**: Build tool and dev server
- **typescript**: Type safety
- **drizzle-kit**: Database migrations and schema management
- **esbuild**: Server-side bundling

## Deployment Strategy

### Build Process
- Frontend: Vite builds React app to `dist/public`
- Backend: esbuild bundles server code to `dist/index.js`
- Database: Drizzle migrations stored in `migrations/` directory

### Environment Configuration
- **DATABASE_URL**: PostgreSQL connection string (required)
- **NODE_ENV**: Environment mode (development/production)

### Scripts
- `npm run dev`: Development server with hot reload
- `npm run build`: Production build for both client and server
- `npm run start`: Production server
- `npm run db:push`: Push database schema changes

### Hosting Requirements
- Node.js runtime environment
- PostgreSQL database (configured for Neon Database)
- Environment variable support for DATABASE_URL
- Static file serving capability for frontend assets

The application is designed to be deployed on platforms like Replit, with development-specific features including runtime error overlays and cartographer integration for enhanced debugging.