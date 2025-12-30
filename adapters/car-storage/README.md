# Car Storage Adapter

## Capabilities

- `car.create` - Create a new car record
- `car.read` - Read car information by ID
- `car.update` - Update existing car record
- `car.delete` - Delete a car record
- `car.list` - List all cars

## Request Format

```json
{
  "capability": "car.create",
  "payload": {
    "brand": "Toyota",
    "model": "Camry",
    "year": 2024,
    "price": 35000
  }
}
```

## Response Format

```json
{
  "id": "car_12345",
  "brand": "Toyota",
  "model": "Camry",
  "year": 2024,
  "price": 35000,
  "created_at": 1234567890
}
```

## Storage

Uses simple file-based storage in `/tmp/car-storage.json` for demo purposes.
In production, replace with a real database.
