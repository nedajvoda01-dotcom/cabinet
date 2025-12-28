# Cabinet Frontend - Setup and Usage Instructions

## Overview

The Cabinet frontend is a React + TypeScript application built with Vite. It provides a desktop-only control panel for managing tasks and viewing pipeline progress.

## Prerequisites

- Node.js 18+ and npm
- PHP 8.3+ (for backend)
- Composer (for backend dependencies)

## Backend Setup

1. Install PHP dependencies:
   ```bash
   cd /path/to/cabinet
   composer install
   ```

2. Start the backend server:
   ```bash
   php -S localhost:8080 -t app/backend/public app/backend/public/index.php
   ```

   The backend API will be available at `http://localhost:8080`

## Frontend Setup

1. Navigate to the frontend directory:
   ```bash
   cd app/frontend
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

3. Start the development server:
   ```bash
   npm run dev
   ```

   The frontend will be available at `http://localhost:3000`

4. Build for production:
   ```bash
   npm run build
   ```

   The built files will be in `app/frontend/dist/`

## Features

### Task List Page (`/tasks`)

- View all tasks in the system
- Create new tasks
- Navigate to task details

### Task Details Page (`/tasks/:id`)

- View task information and status
- View pipeline stages (Parse → Photos → Publish → Export → Cleanup)
- Manually tick the pipeline forward
- View stage outputs (JSON payloads)
- Retry failed tasks (Admin)
- Retry from Dead Letter Queue (Admin)

## Security

The frontend sends the following security headers with each request:
- `x-actor-id`: Actor identifier (demo: `user:demo-user`)
- `x-nonce`: Unique nonce for each request
- `x-key-id`: Key identifier
- `x-signature`: Request signature (demo mode)
- `x-trace-id`: Request tracing ID

**Note:** In the current implementation, security headers use demo values. In production, these should be generated based on proper authentication and key exchange.

## Architecture

The frontend follows these principles:
- **Desktop-only**: No responsive/mobile layouts
- **Contract-driven**: Uses shared contracts from `shared/contracts/implementations/ts`
- **Read-heavy**: Reflects backend state, no business logic
- **No state computation**: Backend is the source of truth

## Project Structure

```
app/frontend/
├── src/
│   ├── api/            # API client
│   ├── contracts/      # Re-exported contracts
│   ├── pages/          # Route-level pages
│   ├── components/     # Reusable components
│   ├── types/          # TypeScript types
│   ├── app/            # App component
│   ├── main.tsx        # Entry point
│   └── index.css       # Global styles
├── public/             # Static assets
├── index.html          # HTML template
├── vite.config.ts      # Vite configuration
├── tsconfig.json       # TypeScript configuration
└── package.json        # Dependencies
```

## API Endpoints Used

- `GET /tasks` - List all tasks
- `GET /tasks/{id}` - Get task details
- `GET /tasks/{id}/outputs` - Get task outputs
- `POST /tasks/create` - Create a new task
- `POST /tasks/{id}/tick` - Manually advance pipeline
- `POST /admin/pipeline/retry` - Retry a failed job (admin)

## Development

- The Vite dev server proxies API requests to `http://localhost:8080`
- Hot module replacement is enabled for fast development
- TypeScript errors are shown in the console and browser

## Error Handling

The frontend displays error messages for:
- 403 Forbidden: Access denied
- 404 Not Found: Resource not found
- 409 Conflict: Idempotency violation
- 500 Internal Server Error: Server error

No stack traces or raw errors are shown to the user.

## Known Limitations

- Security headers use demo values (not production-ready)
- No WebSocket support for real-time updates
- No proper authentication/authorization UI
- Desktop-only (no mobile support by design)

## Next Steps

To integrate real authentication:
1. Implement proper key exchange flow
2. Generate real signatures using the security protocol
3. Store keys securely (not in code)
4. Add login/logout functionality
5. Handle token refresh

## Support

For issues or questions, see the main project README or AGENT.md.
