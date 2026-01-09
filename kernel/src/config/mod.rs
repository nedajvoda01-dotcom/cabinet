// Kernel Configuration Loading
// Validates and loads manifests, routing, and system config at startup
// All validation happens here - fail fast on invalid config

pub mod load_manifests;
pub mod load_routes;
pub mod load_system;
