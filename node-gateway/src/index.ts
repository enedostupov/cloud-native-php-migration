import express, { Request, Response, NextFunction } from "express";
import morgan from "morgan";
import axios from "axios";
import cors from "cors";
import jwt from "jsonwebtoken";

const app = express();
app.use(cors());
app.use(express.json());
app.use(morgan("dev"));

const PHP_API = process.env.PHP_API_URL || "http://php-app";
const JWT_SECRET = process.env.JWT_SECRET || "change-me-in-production";

const rateLimitStore = new Map<string, { count: number; resetTime: number }>();
const RATE_LIMIT_WINDOW = 60 * 1000;
const RATE_LIMIT_MAX_REQUESTS = 100;

function rateLimit(req: Request, res: Response, next: NextFunction) {
  const clientId = req.ip || "unknown";
  const now = Date.now();
  
  let record = rateLimitStore.get(clientId);
  
  if (!record || now > record.resetTime) {
    record = { count: 0, resetTime: now + RATE_LIMIT_WINDOW };
    rateLimitStore.set(clientId, record);
  }
  
  record.count++;
  
  if (record.count > RATE_LIMIT_MAX_REQUESTS) {
    return res.status(429).json({
      error: "rate_limit_exceeded",
      retryAfter: Math.ceil((record.resetTime - now) / 1000),
    });
  }
  
  next();
}

app.use(rateLimit);

app.get("/health", async (_req: Request, res: Response) => {
  const health = {
    gateway: "ok",
    backend: "unknown",
    timestamp: new Date().toISOString(),
  };

  try {
    const resp = await axios.get(`${PHP_API}/health`, { timeout: 2000 });
    health.backend = resp.data?.status === "ok" ? "ok" : "degraded";
    res.status(200).json(health);
  } catch {
    health.backend = "down";
    res.status(503).json(health);
  }
});

app.post("/auth/login", (req: Request, res: Response) => {
  const { username, password } = req.body;

  if (!username || !password) {
    return res.status(400).json({ error: "missing_credentials" });
  }

  if (username === "admin" && password === "admin") {
    const token = jwt.sign({ username, role: "admin" }, JWT_SECRET, {
      expiresIn: "1h",
    });
    
    res.json({ token, expiresIn: 3600 });
  } else {
    res.status(401).json({ error: "invalid_credentials" });
  }
});

function authenticate(req: Request, res: Response, next: NextFunction) {
  const authHeader = req.headers.authorization;
  
  if (!authHeader?.startsWith("Bearer ")) {
    return res.status(401).json({ error: "unauthorized" });
  }

  const token = authHeader.slice(7);
  
  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    (req as any).user = decoded;
    next();
  } catch (error: any) {
    res.status(401).json({ 
      error: error.message === "jwt expired" ? "token_expired" : "invalid_token" 
    });
  }
}

app.use(authenticate);

app.use("/api", async (req: Request, res: Response) => {
  const targetUrl = `${PHP_API}${req.originalUrl}`;

  try {
    const response = await axios({
      method: req.method as any,
      url: targetUrl,
      data: req.body,
      headers: { "Content-Type": "application/json" },
      validateStatus: () => true,
    });

    res.status(response.status).json(response.data);
  } catch (error: any) {
    console.error(`[Proxy Error] ${req.method} ${targetUrl}:`, error.message);
    res.status(502).json({ error: "bad_gateway" });
  }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Gateway running on port ${PORT}`);
  console.log(`JWT auth enabled, rate limit: ${RATE_LIMIT_MAX_REQUESTS}/min`);
});
