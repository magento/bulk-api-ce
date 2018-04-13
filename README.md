# Bulk API Project

Magento uses Web API to cover the persistence operations of the business entities from the external clients. Some integrations require massive invocation of the Web API while persisting entities from the external systems. Usually it causes perfoemance and scalability issues both on the Magento system and on the external clients. This project is intended to provide a way for persisting big amounts of data in the Magento in efficient scalable way for the external system.

## Repositories

Bulk API track consists of 3 repositories:
- current, https://github.com/magento-engcom/bulk-api is for documentation and tasks tracking
- https://github.com/magento/bulk-api-ce is for contributions to the CE part of the Bulk API scope
- https://github.com/magento/bulk-api-ee is for contribution to the EE part of the scope

## Goals

1. Implement support of asynchronous invocation of the magento Web API endpoints: requested operation should not be performed imediatedly, but rather saved to the queue for the later processing.
2. Support invocation of the CRUD APIs with the multiple entities in one request
3. Support of the status tracking API for asyncronous operations
4. Improve performance of persistence operations for the key entities like catalog in the Magento business logic. Resolve deadlocks.

## Design
Technical vision for the Bulk API: https://github.com/magento-engcom/bulk-api/wiki
High Level design for asynchronous operations: https://github.com/magento-engcom/bulk-api/wiki/Asynchronous-Web-API

## Tasks Board

We are using [ZenHub](https://www.zenhub.com/) board to manage stories and tasks and build burndown chart for them. Please install browser pluin to get all teh features of this application.

The Kanban board: https://app.zenhub.com/workspace/o/magento-engcom/bulk-api

## Contributing

Currently, all the functionality of the Message Queue and RabbitMQ integration is part of the Magento EE edition. Most stories as a part of the Bulk API track relies on the Message Queue interfaces. There is already a product decision made of moving the interfaces from the EE to CE edition of Magento. 

All the [Solution Partners](https://magento.com/find-a-partner) of Magento and developers affiliated with them should have access to the EE repositories for this project. If you don't have it yet, please write us at engcom@magento.com

If you are not the Solution Partner but still want to contribute, completion of moving of the Message Queue interfaces to CE should be a prerequisite for this task.

## Installation

### Building the code of Magento from repositories

```
git clone https://github.com/magento/bulk-api-ce.git
cd bulk-api-ce
git clone https://github.com/magento/bulk-api-ee.git
php -f bulk-api-ee/dev/tools/build-ee.php -- --ce-source=. --ee-source=bulk-api-ee --command=link
```
This will clone the repositories and create symlinks from the EE to CE. PLease note that EE is cloned to the subdirectory of CE. This is needed to resolve the templates files references.

### Running RabbitMQ

1. Start Docker container for RabbitMQ 3 with the management plugin and expose AMQP port and Admin UI port:
```
docker run --hostname localhost -p 5672:5672 -p 15672:15672 rabbitmq:3-management
```
2. Go to http://localhost:15672/ login:guest, password:gust to check the statuses of the exchanges and queues

### Installing Magento connected to RabbitMQ

```
bin/magento setup:install 
--backend-frontname="admin" 
--amqp-host="localhost" 
--amqp-port="5672" 
--amqp-user="guest" 
--amqp-password="guest" 
--db-host="localhost" 
--db-name="bulk_api_ce" 
--db-user="root" 
--db-password="root" 
--admin-user="admin" 
--admin-password="admin123" 
--admin-email="vranen@gmail.com" 
--admin-firstname="Eugene" 
--admin-lastname="Tulika" 
--base-url="magento.url" 
--cleanup-database
```
### Trying out PoC code

1. Setup Postman
2. Create Integration in the Magento backend
3. Send Web API request to Magento and ensure that it works
```
http://magento.loc/rest/V1/products POST

{
    "product": {
        "sku": "simple-attempt-1-request-1",
        "type_id": "simple",
        "attribute_set_id": "4",
        "price": "12.22",
        "name": "new_name_simple-attempt-1-request-1",
        "extension_attributes": {
            "stock_item": {
                "qty": 0
            }
        },
        "custom_attributes": [
            {
                "attribute_code": "ap21_size_code",
                "value": "123"
            },
            {
                "attribute_code": "ap21_style_code",
                "value": "abc"
            },
            {
                "attribute_code": "special_price",
                "value": "12.11"
            },
            {
                "attribute_code": "color",
                "value": "4121"
            },
            {
                "attribute_code": "size",
                "value": "3242"
            }
        ]
    }
}
```
4. add `/async` prefix to the Web API endpoint `rest/V1/products` => `rest/async/V1/products`
5. Send request. Navigate to RabbitMQ and see message in the Queue
6. Navigate to the System => Bulk Actions to see the UI of bulk operations and new operation there

