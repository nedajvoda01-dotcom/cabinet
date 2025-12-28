'use strict';

const ActorType = {
  USER: 'user',
  INTEGRATION: 'integration'
};

const ErrorKind = {
  VALIDATION_ERROR: 'validation_error',
  SECURITY_DENIED: 'security_denied',
  NOT_FOUND: 'not_found',
  INTERNAL_ERROR: 'internal_error',
  INTEGRATION_UNAVAILABLE: 'integration_unavailable',
  RATE_LIMITED: 'rate_limited'
};

const HierarchyRole = {
  USER: 'user',
  ADMIN: 'admin',
  SUPER_ADMIN: 'super_admin'
};

const JobStatus = {
  QUEUED: 'queued',
  RUNNING: 'running',
  SUCCEEDED: 'succeeded',
  FAILED: 'failed',
  DEAD_LETTER: 'dead_letter'
};

const PipelineStage = {
  PARSE: 'parse',
  PHOTOS: 'photos',
  PUBLISH: 'publish',
  EXPORT: 'export',
  CLEANUP: 'cleanup'
};

function canonicalJson(value) {
  return encodeValue(value);
}

function encodeValue(value) {
  if (Array.isArray(value)) {
    const normalized = value.map((item) => JSON.parse(encodeValue(item)));
    return JSON.stringify(normalized);
  }

  if (value !== null && typeof value === "object") {
    const entries = Object.entries(value).sort(([a], [b]) => a.localeCompare(b));
    const normalized = {};
    for (const [key, item] of entries) {
      normalized[key] = JSON.parse(encodeValue(item));
    }
    return JSON.stringify(normalized);
  }

  return JSON.stringify(value);
}

module.exports = { canonicalJson, ActorType, ErrorKind, HierarchyRole, JobStatus, PipelineStage };
