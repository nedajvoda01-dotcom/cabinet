# Automation Adapter

## Capabilities

- `workflow.execute` - Execute a workflow
- `workflow.status` - Check workflow execution status
- `workflow.list` - List available workflows

## Request Format

### Execute Workflow

```json
{
  "capability": "workflow.execute",
  "payload": {
    "workflow_id": "car_onboarding",
    "parameters": {
      "car_id": "car_12345"
    }
  }
}
```

### Check Status

```json
{
  "capability": "workflow.status",
  "payload": {
    "execution_id": "exec_12345"
  }
}
```

## Response Format

```json
{
  "execution_id": "exec_12345",
  "workflow_id": "car_onboarding",
  "status": "running",
  "steps_completed": 2,
  "total_steps": 4,
  "started_at": 1234567890
}
```

## Available Workflows

- `car_onboarding` - Onboard a new car to the system
- `price_update` - Update prices for all cars
