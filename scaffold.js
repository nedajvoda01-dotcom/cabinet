#!/usr/bin/env node
/**
 * Autocontent Scaffold Generator (follows AGENT.md)
 *
 * Usage:
 *   node scaffold.js module Cards
 *   node scaffold.js feature publish
 *
 * What it does:
 *   - Creates backend domain module skeleton in backend/src/Modules/<Domain>/
 *   - Creates frontend feature skeleton in frontend/src/features/<domain>/
 *
 * Idempotent:
 *   - Won't overwrite existing files (safe to re-run).
 */

const fs = require("fs");
const path = require("path");

const [,, kind, nameRaw] = process.argv;

if (!kind || !nameRaw) {
  console.log(`
Usage:
  node scaffold.js module <DomainName>
  node scaffold.js feature <domainName>

Examples:
  node scaffold.js module Cards
  node scaffold.js feature publish
`);
  process.exit(1);
}

const projectRoot = process.cwd();

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true });
}

function writeIfMissing(filePath, content) {
  if (fs.existsSync(filePath)) {
    return false;
  }
  fs.writeFileSync(filePath, content, "utf8");
  return true;
}

function pascalCase(s) {
  return s
    .replace(/[_\-\s]+/g, " ")
    .split(" ")
    .filter(Boolean)
    .map(w => w[0].toUpperCase() + w.slice(1))
    .join("");
}

function kebabCase(s) {
  return s
    .replace(/([a-z])([A-Z])/g, "$1-$2")
    .replace(/[_\s]+/g, "-")
    .toLowerCase();
}

function generateBackendModule(domainName) {
  const Domain = pascalCase(domainName);
  const baseDir = path.join(projectRoot, "backend", "src", "Modules", Domain);

  ensureDir(baseDir);

  const files = [
    {
      name: `${Domain}Controller.php`,
      content: `<?php
namespace Modules\\${Domain};

class ${Domain}Controller
{
    private ${Domain}Service $service;

    public function __construct(${Domain}Service $service)
    {
        $this->service = $service;
    }

    // TODO: add controller actions
}
`
    },
    {
      name: `${Domain}Service.php`,
      content: `<?php
namespace Modules\\${Domain};

class ${Domain}Service
{
    // TODO: inject repositories/adapters/queue

    public function __construct()
    {
    }

    // TODO: add domain methods
}
`
    },
    {
      name: `${Domain}Model.php`,
      content: `<?php
namespace Modules\\${Domain};

class ${Domain}Model
{
    // TODO: define entity fields + hydration
}
`
    },
    {
      name: `${Domain}Schemas.php`,
      content: `<?php
namespace Modules\\${Domain};

class ${Domain}Schemas
{
    // TODO: request/response validation schemas
}
`
    },
    {
      name: `${Domain}Jobs.php`,
      content: `<?php
namespace Modules\\${Domain};

class ${Domain}Jobs
{
    // TODO: enqueue async jobs via Queues
}
`
    },
    {
      name: "README.md",
      content: `# ${Domain} module

Domain responsibilities:
- TODO

Async jobs:
- TODO
`
    }
  ];

  let created = 0;
  for (const f of files) {
    if (writeIfMissing(path.join(baseDir, f.name), f.content)) created++;
  }

  console.log(`Backend module ${Domain}: ${created} files created in ${baseDir}`);
}

function generateFrontendFeature(domainName) {
  const domain = kebabCase(domainName);
  const baseDir = path.join(projectRoot, "frontend", "src", "features", domain);

  ensureDir(baseDir);
  ensureDir(path.join(baseDir, "ui"));

  const files = [
    {
      name: "api.ts",
      content: `// ${domain} feature API layer (React Query / fetch wrappers)
// Follows AGENT.md: features may import design + shared only.

export function use${pascalCase(domain)}() {
  // TODO
}
`
    },
    {
      name: "model.ts",
      content: `// ${domain} feature model/types/selectors

export type ${pascalCase(domain)}Entity = {
  id: string;
  // TODO fields
};
`
    },
    {
      name: "schemas.ts",
      content: `// ${domain} feature validation schemas
// e.g., zod / yup

export const ${pascalCase(domain)}Schema = {};
`
    },
    {
      name: "ui/index.ts",
      content: `// Barrel for UI components of ${domain}
export {};
`
    },
    {
      name: "index.ts",
      content: `export * from "./api";
export * from "./model";
export * from "./schemas";
export * as ${pascalCase(domain)}UI from "./ui";
`
    },
    {
      name: "README.md",
      content: `# ${domain} feature

Responsibilities:
- TODO

UI components:
- TODO
`
    }
  ];

  let created = 0;
  for (const f of files) {
    const target =
      f.name.includes("/") ? path.join(baseDir, ...f.name.split("/")) : path.join(baseDir, f.name);
    const dir = path.dirname(target);
    ensureDir(dir);
    if (writeIfMissing(target, f.content)) created++;
  }

  console.log(`Frontend feature ${domain}: ${created} files created in ${baseDir}`);
}

if (kind === "module") {
  generateBackendModule(nameRaw);
} else if (kind === "feature") {
  generateFrontendFeature(nameRaw);
} else {
  console.error(`Unknown kind: ${kind}. Use "module" or "feature".`);
  process.exit(1);
}
