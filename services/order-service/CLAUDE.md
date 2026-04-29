# Order Service

Symfony 7. Uses Symfony Workflow component for order state machine.
States: pending → confirmed → shipped → delivered → cancelled

Listens for: cart.checkout_requested
Publishes: order.confirmed, order.cancelled

DB: order_db (MySQL). No direct DB access from other services.