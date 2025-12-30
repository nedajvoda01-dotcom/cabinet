# Pricing Adapter

## Capabilities

- `price.calculate` - Calculate price based on rules
- `price.rule.create` - Create or update a pricing rule
- `price.rule.list` - List all pricing rules

## Request Format

### Calculate Price

```json
{
  "capability": "price.calculate",
  "payload": {
    "brand": "Toyota",
    "year": 2020,
    "base_price": 35000
  }
}
```

### Create Rule

```json
{
  "capability": "price.rule.create",
  "payload": {
    "key": "brand_premium.Tesla",
    "value": 1.8
  }
}
```

## Response Format

```json
{
  "base_price": 35000,
  "depreciation": 0.8145,
  "brand_multiplier": 1.0,
  "final_price": 28507.5,
  "age_years": 4
}
```

## Pricing Rules

Default rules include:
- Base rate
- Year-based depreciation (5% per year)
- Brand premium multipliers
