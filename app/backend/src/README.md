# cabinet/app/backend/src/README.md — Backend Layers & Responsibility Model

## Location

cabinet/app/backend/src/README.md

---

## Purpose

This document defines the **internal structure of the backend source code**.

It explains:
- how backend layers are separated
- what each layer is responsible for
- which dependencies are allowed
- where specific logic must live

This README is **normative** for backend structure.

---

## Layer Overview

The backend source is divided into **explicit architectural layers**:

- `Domain`
- `Application`
- `Infrastructure`
- `Http`
- `Bootstrap`

Each layer:
- has a single responsibility
- has strict dependency rules
- must not leak concerns into other layers

---

## Dependency Rules (CRITICAL)

Allowed dependency direction:

