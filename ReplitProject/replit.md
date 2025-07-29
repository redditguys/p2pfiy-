# PixelTask Marketplace Platform

## Overview

This is a comprehensive freelance marketplace platform with dual implementations:
1. **TypeScript/React Version**: Modern full-stack application with admin panel for payment management
2. **PHP Version**: Complete all-in-one marketplace with admin, client, and worker dashboards

The platform connects clients who post tasks with skilled freelancers worldwide, featuring secure payment processing, dispute resolution, and comprehensive admin controls.

## User Preferences

Preferred communication style: Simple, everyday language.

## Recent Changes (July 29, 2025)

✓ Created comprehensive PHP marketplace platform
✓ Built admin dashboard with payment management system  
✓ Implemented client dashboard for task posting and management
✓ Developed worker dashboard with earnings and payout system
✓ Added secure authentication with role-based access control
✓ Integrated payment processing with commission calculation
✓ Created dispute resolution system for conflict management

## System Architecture

### Frontend Architecture
- **Framework**: React 18 with TypeScript
- **Build Tool**: Vite for fast development and optimized builds
- **UI Framework**: Shadcn/ui components built on Radix UI primitives
- **Styling**: Tailwind CSS with custom design tokens
- **State Management**: TanStack Query (React Query) for server state
- **Routing**: Wouter for lightweight client-side routing

### Backend Architecture
- **Runtime**: Node.js with Express.js framework
- **Language**: TypeScript with ES modules
- **API Pattern**: RESTful API with JSON responses
- **Development**: Hot reload with tsx for TypeScript execution

### Database Layer
- **Database**: PostgreSQL (configured for Neon serverless)
- **ORM**: Drizzle ORM with type-safe queries
- **Migration**: Drizzle Kit for schema management
- **Connection**: @neondatabase/serverless for serverless PostgreSQL

## Key Components

### Database Schema
The application manages four main entities:
- **Users**: Support client, worker, and admin roles with authentication
- **Transactions**: Track payments between clients and workers with commission
- **Disputes**: Handle transaction disputes with status tracking
- **Payouts**: Manage worker payments with processing states
- **Platform Settings**: Configurable commission rates and processing fees

### Authentication System
- Basic email/password authentication for admin users
- Password hashing with bcrypt
- Session-based authentication (prepared for but not fully implemented)

### Admin Dashboard Features
- **Stats Overview**: Revenue tracking, active transactions, pending disputes
- **Transaction Management**: View, filter, and manage all platform transactions
- **User Management**: Monitor user activity and registrations
- **Payout Queue**: Process worker payments in batches
- **Commission Settings**: Configure platform fees and payout schedules
- **Dispute Resolution**: Handle and resolve transaction disputes

### UI Components
- Comprehensive component library using Radix UI primitives
- Custom styling with Tailwind CSS and CSS variables
- Responsive design with mobile-first approach
- Toast notifications for user feedback
- Form handling with React Hook Form and Zod validation

## Data Flow

1. **Authentication Flow**: Admin logs in through `/admin/login` endpoint
2. **Dashboard Data**: Stats and overview data fetched from `/api/admin/stats`
3. **Entity Management**: CRUD operations through RESTful endpoints
4. **Real-time Updates**: React Query handles cache invalidation and refetching
5. **Error Handling**: Centralized error handling with toast notifications

## External Dependencies

### Core Libraries
- **@neondatabase/serverless**: PostgreSQL connection for serverless environments
- **drizzle-orm**: Type-safe database queries and schema management
- **@tanstack/react-query**: Server state management and caching
- **bcrypt**: Password hashing for security
- **zod**: Runtime type validation and schema parsing

### UI Dependencies
- **@radix-ui/***: Accessible UI component primitives
- **tailwindcss**: Utility-first CSS framework
- **lucide-react**: Icon library for consistent iconography
- **wouter**: Lightweight routing solution

### Development Tools
- **vite**: Fast build tool and development server
- **typescript**: Static type checking
- **tsx**: TypeScript execution for Node.js
- **@replit/vite-plugin-***: Replit-specific development enhancements

## Deployment Strategy

### Build Process
- Frontend builds to `dist/public` using Vite
- Backend bundles to `dist/index.js` using esbuild
- Single deployment artifact with both frontend and backend

### Environment Configuration
- Development: Hot reload with Vite dev server and tsx
- Production: Compiled JavaScript with static file serving
- Database: Environment variable `DATABASE_URL` for connection

### Scalability Considerations
- Serverless-ready architecture with @neondatabase/serverless
- Stateless backend design for horizontal scaling
- Static asset serving through Express in production
- Connection pooling handled by Neon serverless driver

The application follows a monorepo structure with shared TypeScript types between frontend and backend, ensuring type safety across the entire stack. The admin panel provides comprehensive management capabilities for the payment platform while maintaining a clean, responsive user interface.