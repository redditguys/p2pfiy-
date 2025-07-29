import express, { type Request, Response, NextFunction } from "express";
import { registerRoutes } from "./routes";
import { setupVite, serveStatic, log } from "./vite";
import path from "path";
import fs from "fs";

const app = express();
app.use(express.json());
app.use(express.urlencoded({ extended: false }));

// Serve PHP marketplace platform
const phpMarketplacePath = path.resolve(import.meta.dirname, "..", "php-marketplace");
app.use("/php-marketplace", express.static(phpMarketplacePath));

// Serve PHP files as static HTML for demo purposes
app.get("/php-marketplace/*", (req, res, next) => {
  const requestedPath = req.params[0] as string || "index.php";
  const filePath = path.join(phpMarketplacePath, requestedPath);
  
  if (fs.existsSync(filePath)) {
    if (filePath.endsWith('.php')) {
      // For demo purposes, serve PHP files as HTML
      res.setHeader('Content-Type', 'text/html');
      fs.readFile(filePath, 'utf8', (err, data) => {
        if (err) {
          next();
          return;
        }
        res.send(data);
      });
    } else {
      next();
    }
  } else {
    next();
  }
});

// Redirect root marketplace access to PHP platform
app.get("/marketplace", (req, res) => {
  res.redirect("/php-marketplace/");
});

app.get("/", (req, res, next) => {
  // Check if accessing marketplace
  if (req.headers.accept?.includes('text/html')) {
    const marketplaceIndex = path.join(phpMarketplacePath, "index.php");
    if (fs.existsSync(marketplaceIndex)) {
      res.setHeader('Content-Type', 'text/html');
      fs.readFile(marketplaceIndex, 'utf8', (err, data) => {
        if (err) {
          next();
          return;
        }
        res.send(data);
      });
      return;
    }
  }
  next();
});

app.use((req, res, next) => {
  const start = Date.now();
  const path = req.path;
  let capturedJsonResponse: Record<string, any> | undefined = undefined;

  const originalResJson = res.json;
  res.json = function (bodyJson, ...args) {
    capturedJsonResponse = bodyJson;
    return originalResJson.apply(res, [bodyJson, ...args]);
  };

  res.on("finish", () => {
    const duration = Date.now() - start;
    if (path.startsWith("/api")) {
      let logLine = `${req.method} ${path} ${res.statusCode} in ${duration}ms`;
      if (capturedJsonResponse) {
        logLine += ` :: ${JSON.stringify(capturedJsonResponse)}`;
      }

      if (logLine.length > 80) {
        logLine = logLine.slice(0, 79) + "…";
      }

      log(logLine);
    }
  });

  next();
});

(async () => {
  const server = await registerRoutes(app);

  app.use((err: any, _req: Request, res: Response, _next: NextFunction) => {
    const status = err.status || err.statusCode || 500;
    const message = err.message || "Internal Server Error";

    res.status(status).json({ message });
    throw err;
  });

  // importantly only setup vite in development and after
  // setting up all the other routes so the catch-all route
  // doesn't interfere with the other routes
  if (app.get("env") === "development") {
    await setupVite(app, server);
  } else {
    serveStatic(app);
  }

  // ALWAYS serve the app on the port specified in the environment variable PORT
  // Other ports are firewalled. Default to 5000 if not specified.
  // this serves both the API and the client.
  // It is the only port that is not firewalled.
  const port = parseInt(process.env.PORT || '5000', 10);
  server.listen({
    port,
    host: "0.0.0.0",
    reusePort: true,
  }, () => {
    log(`serving on port ${port}`);
  });
})();
