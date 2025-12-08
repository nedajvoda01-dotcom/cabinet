#!/usr/bin/env node
/**
 * init_project.js — Bootstrap Autocontent repo skeleton
 *
 * Runs scaffold.js to generate the standard backend modules and frontend features
 * described in AGENT.md.
 *
 * Usage:
 *   node init_project.js
 *
 * Notes:
 *   - Safe to re-run: scaffold.js is idempotent (won't overwrite existing files).
 *   - Requires Node.js installed.
 */

const { execSync } = require("child_process");
const fs = require("fs");
const path = require("path");

const root = process.cwd();
const scaffoldPath = path.join(root, "scaffold.js");

if (!fs.existsSync(scaffoldPath)) {
  console.error("scaffold.js not found in repo root. Put scaffold.js next to init_project.js.");
  process.exit(1);
}

function run(cmd) {
  console.log(`\n> ${cmd}`);
  execSync(cmd, { stdio: "inherit" });
}

function ensureDir(p) {
  fs.mkdirSync(p, { recursive: true });
}

/** 1) Ensure frozen root dirs exist */
[
  "backend",
  "frontend",
  "external",
  "tests",
  "docs",
  "infra"
].forEach(d => ensureDir(path.join(root, d)));

console.log("Root directories ensured.");

/** 2) Standard backend modules */
const backendModules = [
  "Auth",
  "Users",
  "Cards",
  "Parser",
  "Photos",
  "Export",
  "Publish",
  "Robot",
  "Admin"
];

backendModules.forEach(m => run(`node ${scaffoldPath} module ${m}`));

/** 3) Standard frontend features */
const frontendFeatures = [
  "cards",
  "parser",
  "photos",
  "export",
  "publish",
  "admin"
];

frontendFeatures.forEach(f => run(`node ${scaffoldPath} feature ${f}`));

console.log("\nBootstrap complete.");
console.log("Next steps:");
console.log("1) Fill module/feature READMEs with responsibilities.");
console.log("2) Add routes/endpoints per docs/api-docs/openapi.yaml.");
console.log("3) Configure external service URLs in backend/src/Config/endpoints.php.");
