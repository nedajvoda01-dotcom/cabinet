# Main UI Application

UI intentionally not implemented in core; this is sandbox track.

## Purpose

This directory serves as a placeholder for the UI application. The actual UI implementation is outside the scope of the sealed core system and belongs in a separate sandbox track.

## Structure

The UI is registered via `manifest.yaml` at the parent level, which defines:
- UI metadata and capabilities
- Permissions and profiles
- Routes and dependencies

## Backend Integration

The UI communicates exclusively with the `backend_ui` module, never calling other modules directly. All routing goes through the backend gateway which enforces permission checks.

## Development

For UI development, refer to the sandbox track documentation. The core system only provides:
- Registration and manifest validation
- Routing configuration
- Permission enforcement boundaries

No actual UI assets, components, or application code exist in this sealed core.
