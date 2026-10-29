# TukodTrack Entity Relationship Diagram (revision working diagram)

This diagram summarizes relationships exposed by the current application models and new revision migration. Before submission, compare every field and FK with the actual PostgreSQL schema (Neon SQL Editor) and the remaining Laravel migrations; it is not a certified full schema extract.

```mermaid
erDiagram
  USERS ||--o{ SALES : cashier_id
  USERS ||--o{ PURCHASES : created_by
  USERS ||--o{ DELIVERIES : received_by
  USERS ||--o{ STOCK_MOVEMENTS : recorded_by
  USERS ||--o{ STOCK_ADJUSTMENTS : requested_by
  CUSTOM_ROLES ||--o{ USERS : "logical role slug"
  CATEGORIES ||--o{ PRODUCTS : category_id
  SUPPLIERS ||--o{ PURCHASES : supplier_id
  PURCHASES ||--|{ PURCHASE_ITEMS : purchase_id
  PRODUCTS ||--o{ PURCHASE_ITEMS : product_id
  PURCHASES ||--o{ DELIVERIES : purchase_id
  DELIVERIES ||--o{ DELIVERY_ITEMS : delivery_id
  PURCHASE_ITEMS ||--o{ DELIVERY_ITEMS : purchase_item_id
  SALES ||--|{ SALE_ITEMS : sale_id
  PRODUCTS ||--o{ SALE_ITEMS : product_id
  PRODUCTS ||--o{ STOCK_MOVEMENTS : product_id
  PRODUCTS ||--o{ STOCK_ADJUSTMENTS : product_id
  SALE_ITEMS ||--o{ STOCK_MOVEMENTS : sale_item_id
  PURCHASE_ITEMS ||--o{ STOCK_MOVEMENTS : purchase_item_id
  DELIVERY_ITEMS ||--o{ STOCK_MOVEMENTS : delivery_item_id
  STOCK_ADJUSTMENTS ||--o{ STOCK_MOVEMENTS : adjustment_id
  USERS {
    bigint id PK
    string name
    string email
    string role
  }
  CUSTOM_ROLES {
    bigint id PK
    string slug UK
    string name
    string base_role
  }
  PRODUCTS {
    bigint id PK
    bigint category_id FK
    string name
    decimal quantity_on_hand
  }
  CATEGORIES { bigint id PK }
  SUPPLIERS { bigint id PK }
  PURCHASES { bigint id PK }
  PURCHASE_ITEMS { bigint id PK }
  DELIVERIES {
    bigint id PK
    string delivery_reference
    string proof_received_name
    text proof_notes
    timestamp proof_confirmed_at
  }
  DELIVERY_ITEMS { bigint id PK }
  SALES { bigint id PK }
  SALE_ITEMS { bigint id PK }
  STOCK_MOVEMENTS { bigint id PK }
  STOCK_ADJUSTMENTS { bigint id PK }
```

**Caution:** The custom role association uses a role slug string rather than a database foreign key. Delivery proof fields record a staff acknowledgement, not a photo or handwritten signature.
