// Shared Reverb connection and benchmark statistics utilities for the Node benchmark scripts.
import crypto from "node:crypto";
import { URLSearchParams } from "node:url";
import WebSocket from "ws";

const required = (name) => {
  const value = process.env[name];
  if (!value) throw new Error(`Missing required environment variable: ${name}`);
  return value;
};

export const benchmarkEnv = {
  url: required("BENCH_URL"),
  code: required("BENCH_CODE"),
  secret: required("BENCH_SECRET"),
  appKey: required("REVERB_APP_KEY"),
};

globalThis.clockOffsetMs = 0;

export const syncClock = async (url) => {
  try {
    const response = await fetch(url);
    if (!response.ok) throw new Error(`Clock sync request failed: ${response.status}`);
    const dateHeader = response.headers.get("date");
    if (!dateHeader || !Number.isFinite(Date.parse(dateHeader))) throw new Error("Clock sync response has no valid Date header");
    globalThis.clockOffsetMs = Date.parse(dateHeader) - Date.now();
  } catch (error) {
    console.warn(`Clock sync unavailable; using local clock: ${error.message}`);
    globalThis.clockOffsetMs = 0;
  }
  return globalThis.clockOffsetMs;
};

export const utcTimestamp = () => Date.now();

export const mean = (values) => values.reduce((sum, value) => sum + value, 0) / Math.max(1, values.length);

export const median = (values) => {
  const sorted = [...values].sort((a, b) => a - b);
  const middle = Math.floor(sorted.length / 2);
  return sorted.length % 2 ? sorted[middle] : (sorted[middle - 1] + sorted[middle]) / 2;
};

export const percentile = (values, p) => {
  if (!values.length) return 0;
  const sorted = [...values].sort((a, b) => a - b);
  const index = Math.ceil((p / 100) * sorted.length) - 1;
  return sorted[Math.max(0, index)];
};

export const statistics = (values) => {
  const average = mean(values);
  const variance = values.reduce((sum, value) => sum + (value - average) ** 2, 0) / Math.max(1, values.length);
  return { mean: average, median: median(values), p95: percentile(values, 95), p99: percentile(values, 99), min: Math.min(...values), max: Math.max(...values), stddev: Math.sqrt(variance) };
};

export const writeCsv = (path, rows, columns) => {
  const lines = [columns.join(","), ...rows.map((row) => columns.map((column) => String(row[column] ?? "")).join(","))];
  return import("node:fs/promises").then(({ writeFile }) => writeFile(path, `${lines.join("\n")}\n`, "utf8"));
};

// Read all Set-Cookie values supported by the active Node fetch implementation.
const responseCookies = (response) => response.headers.getSetCookie?.() ?? [response.headers.get("set-cookie")].filter(Boolean);

// Update the in-memory jar with cookie name/value pairs from a response.
const storeCookies = (jar, response) => {
  for (const cookie of responseCookies(response)) {
    const pair = cookie.split(";", 1)[0];
    const separator = pair.indexOf("=");
    if (separator > 0) jar.set(pair.slice(0, separator), pair.slice(separator + 1));
  }
};

// Serialize the current cookie jar for an HTTP request.
const cookieHeader = (jar) => [...jar].map(([name, value]) => `${name}=${value}`).join("; ");

// Send a request while preserving cookies across redirect responses.
const fetchWithCookies = async (url, options, jar) => {
  let currentUrl = url;
  let method = options.method ?? "GET";
  let body = options.body;

  for (let redirects = 0; redirects <= 10; redirects++) {
    const headers = new Headers(options.headers);
    const cookies = cookieHeader(jar);
    if (cookies) headers.set("Cookie", cookies);
    const response = await fetch(currentUrl, { ...options, method, body, headers, redirect: "manual" });
    storeCookies(jar, response);

    if (![301, 302, 303, 307, 308].includes(response.status)) return response;
    const location = response.headers.get("location");
    if (!location) return response;
    if (response.status === 303 || ([301, 302].includes(response.status) && method === "POST")) {
      method = "GET";
      body = undefined;
    }
    currentUrl = new URL(location, currentUrl);
  }

  throw new Error("Too many redirects while establishing the benchmark session");
};

// Extract Laravel's CSRF token from rendered HTML or the XSRF-TOKEN cookie.
const extractCsrfToken = (html, jar) => {
  const metaTag = [...html.matchAll(/<meta\b[^>]*>/gi)].map(([tag]) => tag).find((tag) => {
    const name = tag.match(/\bname\s*=\s*["']([^"']+)["']/i)?.[1];
    return name?.toLowerCase() === "csrf-token";
  });
  const metaToken = metaTag?.match(/\bcontent\s*=\s*["']([^"']+)["']/i)?.[1];
  if (metaToken) return metaToken;
  const cookieToken = jar.get("XSRF-TOKEN");
  if (!cookieToken) return null;
  try {
    return decodeURIComponent(cookieToken);
  } catch {
    return cookieToken;
  }
};

// Establish a Laravel session and join the benchmark session before channel auth.
const establishSession = async ({ url, code }) => {
  const jar = new Map();
  const rootResponse = await fetchWithCookies(new URL("/", url), {}, jar);
  const rootHtml = await rootResponse.text();
  let csrfToken = extractCsrfToken(rootHtml, jar);
  if (!csrfToken) throw new Error("Could not find a CSRF token on the home page or in cookies");

  const joinResponse = await fetchWithCookies(new URL("/session/join", url), {
    method: "POST",
    headers: {
      "X-CSRF-TOKEN": csrfToken,
      "X-Requested-With": "XMLHttpRequest",
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({ code }),
  }, jar);
  const joinHtml = await joinResponse.text();
  csrfToken = extractCsrfToken(joinHtml, jar) ?? csrfToken;
  if (!joinResponse.ok) throw new Error(`Session join failed: ${joinResponse.status} ${joinHtml.slice(0, 200)}`);
  return { jar, csrfToken };
};

export const connectSocket = async ({ url, code, appKey }) => {
  const { jar, csrfToken } = await establishSession({ url, code });
  const httpUrl = new URL(url);
  const scheme = process.env.REVERB_SCHEME ?? httpUrl.protocol.slice(0, -1);
  const socketProtocol = scheme === "https" ? "wss:" : "ws:";
  const socketHost = process.env.REVERB_HOST ?? httpUrl.hostname;
  const socketPort = process.env.REVERB_PORT ?? (scheme === "https" ? "443" : "8080");
  const socketUrl = new URL(`${socketProtocol}//${socketHost}:${socketPort}/app/${appKey}`);
  socketUrl.search = new URLSearchParams({ protocol: "7", client: "js", version: "8.4.0", flash: "false" });
  const socket = new WebSocket(socketUrl);
  const socketId = await new Promise((resolve, reject) => {
    const timer = setTimeout(() => reject(new Error("Timed out waiting for Reverb connection")), 5000);
    const onMessage = (raw) => {
      const event = JSON.parse(raw.toString());
      if (event.event !== "pusher:connection_established") return;
      clearTimeout(timer);
      socket.off("message", onMessage);
      socket.off("error", onError);
      const connection = typeof event.data === "string" ? JSON.parse(event.data) : event.data;
      resolve(connection.socket_id);
    };
    const onError = (error) => {
      clearTimeout(timer);
      socket.off("message", onMessage);
      reject(error);
    };
    socket.on("message", onMessage);
    socket.once("error", onError);
  });

  const response = await fetchWithCookies(new URL("/broadcasting/auth", url), {
    method: "POST",
    headers: {
      "X-CSRF-TOKEN": csrfToken,
      "X-Requested-With": "XMLHttpRequest",
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({ channel_name: `private-session.${code}`, socket_id: socketId }),
  }, jar);
  const responseBody = await response.text();
  if (response.status !== 200) {
    console.error(`Broadcast authentication failed (${response.status}): ${responseBody.slice(0, 200)}`);
    socket.close();
    throw new Error(`Broadcast authentication failed: ${response.status}`);
  }
  const auth = JSON.parse(responseBody);
  return new Promise((resolve, reject) => {
    const channel = `private-session.${code}`;
    const timer = setTimeout(() => reject(new Error("Timed out waiting for channel subscription")), 5000);
    const onMessage = (raw) => {
      const event = JSON.parse(raw.toString());
      if (event.event === "pusher_internal:subscription_succeeded" && event.channel === channel) {
        clearTimeout(timer);
        socket.off("message", onMessage);
        resolve({ socket, socketId, channel });
      }
    };
    socket.on("message", onMessage);
    socket.send(JSON.stringify({ event: "pusher:subscribe", data: { channel, auth: auth.auth } }));
  });
};

export const waitFor = (socket, event, timeout = 5000) => new Promise((resolve, reject) => {
  const timer = setTimeout(() => reject(new Error(`Timed out waiting for ${event}`)), timeout);
  const handler = (raw) => {
    const payload = JSON.parse(raw.toString());
    if (payload.event === event) {
      socket.off("message", handler);
      clearTimeout(timer);
      resolve(payload.data);
    }
  };
  socket.on("message", handler);
});

export const clientEvent = (socket, event, data) => {
  socket.send(JSON.stringify({ event: `client-${event}`, data }));
};

export const printSummary = (label, values) => {
  const result = statistics(values);
  console.log(`${label}: mean=${result.mean.toFixed(2)}ms median=${result.median.toFixed(2)}ms p95=${result.p95.toFixed(2)}ms p99=${result.p99.toFixed(2)}ms min=${result.min.toFixed(2)}ms max=${result.max.toFixed(2)}ms stddev=${result.stddev.toFixed(2)}ms`);
  return result;
};
