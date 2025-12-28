#!/usr/bin/env node
const fs = require('fs');
const path = require('path');
const contracts = require('../implementations/ts/index.cjs');

const [, , vectorPath] = process.argv;
if (!vectorPath) {
  console.error('Usage: parity.js <vector-path>');
  process.exit(1);
}

const absolutePath = path.resolve(vectorPath);
const payload = JSON.parse(fs.readFileSync(absolutePath, 'utf8'));
process.stdout.write(contracts.canonicalJson(payload));
