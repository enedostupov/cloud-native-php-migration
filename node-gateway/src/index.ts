import express, { Request, Response } from "express";
import morgan from "morgan";
import axios from "axios";
import cors from "cors";

const app = express();

app.use(cors());
app.use(express.json());
app.use(morgan("dev"));

const PHP_API = process.env.PHP_API_URL || "http://php-app";

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
    res.status(502).json({ 
      error: "bad_gateway", 
      message: "Failed to reach backend" 
    });
  }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Node Gateway running on port ${PORT}`);
});
